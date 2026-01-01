from sqlalchemy.orm import Session
from sqlalchemy import select, and_, or_, not_, desc, asc, func, literal
from typing import List, Optional, Any, Dict
import json
from sqlalchemy import inspect
from app.core.database import Base

class SelectManager:
    def __init__(self, db: Session, model_class):
        self.db = db
        self.model_class = model_class
        self.base_query = select(model_class)
        self.pagination_applied = False

    def apply_where(self, where: List[Dict]):
        if not where:
            return

        conditions = []
        for item in where:
            condition = self._get_where_part(item)
            if condition is not None:
                conditions.append(condition)

        if conditions:
            self.base_query = self.base_query.where(and_(*conditions))

    def _get_where_part(self, item: Dict):
        type_ = item.get('type')
        attribute = item.get('attribute') or item.get('field')
        value = item.get('value')

        if not type_:
            return None

        # Logic operators
        if type_ == 'or':
            if not isinstance(value, list): return None
            sub_conditions = [self._get_where_part(sub) for sub in value]
            sub_conditions = [c for c in sub_conditions if c is not None]
            return or_(*sub_conditions) if sub_conditions else None

        if type_ == 'and':
            if not isinstance(value, list): return None
            sub_conditions = [self._get_where_part(sub) for sub in value]
            sub_conditions = [c for c in sub_conditions if c is not None]
            return and_(*sub_conditions) if sub_conditions else None

        if not attribute:
            return None

        # Resolve model column
        column = self._get_column(attribute)
        if column is None:
            # TODO: Handle relationships or ignore
            return None

        if type_ == 'equals':
            return column == value
        elif type_ == 'notEquals':
            return column != value
        elif type_ == 'contains':
            return column.like(f"%{value}%")
        elif type_ == 'notContains':
            return not_(column.like(f"%{value}%"))
        elif type_ == 'startsWith':
            return column.like(f"{value}%")
        elif type_ == 'endsWith':
            return column.like(f"%{value}")
        elif type_ == 'greaterThan':
            return column > value
        elif type_ == 'lessThan':
            return column < value
        elif type_ == 'greaterThanOrEquals':
            return column >= value
        elif type_ == 'lessThanOrEquals':
            return column <= value
        elif type_ == 'in':
            if isinstance(value, list):
                return column.in_(value)
        elif type_ == 'notIn':
            if isinstance(value, list):
                return not_(column.in_(value))
        elif type_ == 'isNull':
            return column.is_(None)
        elif type_ == 'isNotNull':
            return column.is_not(None)
        elif type_ == 'isTrue':
            return column == True
        elif type_ == 'isFalse':
            return column == False

        return None

    def _get_column(self, attribute_name: str):
        # Handle snake_case vs camelCase mapping
        # Inspect the model to find the column
        mapper = inspect(self.model_class).mapper

        # 1. Check if attribute matches property key
        if attribute_name in mapper.all_orm_descriptors:
             desc = mapper.all_orm_descriptors[attribute_name]
             if hasattr(desc, 'property') and hasattr(desc.property, 'columns'):
                 return desc.property.columns[0]

        # 2. Iterate properties to find by column name match or snake_case conversion
        for prop in mapper.iterate_properties:
            if hasattr(prop, 'columns'):
                col = prop.columns[0]
                # Match against column name (often same as attribute name, but sometimes specified in Column("name"))
                # But here attribute_name comes from API which is typically camelCase.
                # In User model: user_name = Column("userName", ...)
                # If attribute_name is "userName", we want this column.
                if col.name == attribute_name:
                    return col

                # Also check property key (user_name)
                if prop.key == attribute_name:
                    return col

        return None

    def apply_order(self, sort_by: str, asc_order: bool = True):
        if not sort_by:
            return

        column = self._get_column(sort_by)
        if column is not None:
            if asc_order:
                self.base_query = self.base_query.order_by(asc(column))
            else:
                self.base_query = self.base_query.order_by(desc(column))

    def apply_limit(self, offset: int = 0, max_size: int = 20):
        # Store for execution time or apply to a separate query object if we want total count
        self.offset = offset
        self.limit = max_size
        self.pagination_applied = True

    def execute(self):
        # Count total before limit/offset
        # We need to make sure we are counting from the filtered query
        # Using subquery might be slow but safe for complex queries
        count_query = select(func.count()).select_from(self.base_query.subquery())
        total = self.db.scalar(count_query)

        query = self.base_query
        if self.pagination_applied:
            if self.offset is not None:
                query = query.offset(self.offset)
            if self.limit is not None:
                query = query.limit(self.limit)

        result = self.db.execute(query)
        records = result.scalars().all()
        return records, total

    def apply_filter(self, attribute: str, value: Any):
        """Applies a simple equality filter."""
        column = self._get_column(attribute)
        if column is not None:
            self.base_query = self.base_query.where(column == value)
        else:
            # FAIL CLOSED: If we try to filter by something that doesn't exist.
            # Especially critical for 'assignedUserId' in ACL checks.
            # If we don't apply the filter, we return everything, which is a leak.
            self.base_query = self.base_query.where(literal(False))

    def apply_team_access_filter(self, user_id: str, team_ids: List[str]):
        """
        Applies filter for 'team' access level.
        Access is granted if:
        1. Record is owned by user (assignedUserId == user_id)
        OR
        2. Record is assigned to one of the user's teams (via 'teams' relationship)
        """

        # Determine if model has 'assignedUserId'
        assigned_user_col = self._get_column('assignedUserId')

        # Check if model has 'teams' relationship
        mapper = inspect(self.model_class).mapper
        teams_rel = mapper.relationships.get('teams')

        conditions = []

        if assigned_user_col is not None:
            conditions.append(assigned_user_col == user_id)

        if teams_rel and team_ids:
             TeamClass = teams_rel.mapper.class_
             conditions.append(getattr(self.model_class, 'teams').any(TeamClass.id.in_(team_ids)))

        if conditions:
            self.base_query = self.base_query.where(or_(*conditions))
        else:
            # FAIL CLOSED: If we can't verify ownership or team membership (no cols), deny access.
            # This happens if the entity doesn't support ownership/teams but ACL requires it.
            self.base_query = self.base_query.where(literal(False))
