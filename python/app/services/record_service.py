from typing import Any, List, Optional
from sqlalchemy.orm import Session
from sqlalchemy import inspect
from app.models.user import User
from app.models.standard_entities import Account, Contact
from app.models.acl_entities import Role, Team
from app.services.metadata import metadata_service
from app.services.acl_service import acl_service
from app.core.select_manager import SelectManager
import secrets
import string
import re

def generate_id():
    alphabet = string.ascii_letters + string.digits
    return ''.join(secrets.choice(alphabet) for i in range(24))

def to_snake_case(name):
    name = re.sub('(.)([A-Z][a-z]+)', r'\1_\2', name)
    return re.sub('([a-z0-9])([A-Z])', r'\1_\2', name).lower()

class RecordService:
    def __init__(self, db: Session, user: User = None):
        self.db = db
        self.user = user
        # Registry of models. In a real dynamic system, this would be more complex.
        self.models = {
            "User": User,
            "Account": Account,
            "Contact": Contact,
            "Role": Role,
            "Team": Team
        }

    def _get_model(self, entity_name: str):
        if entity_name not in self.models:
            raise ValueError(f"Entity {entity_name} not found")
        return self.models[entity_name]

    def _get_record_data(self, record) -> dict:
        """Converts a SQLAlchemy record to a dictionary, handling camelCase keys."""
        data = {}
        mapper = inspect(record).mapper
        for prop in mapper.iterate_properties:
            if hasattr(prop, 'columns'):
                col = prop.columns[0]
                # Use the column name (which matches the DB and usually the JSON)
                # If column name is same as attribute, or if we explicitly defined name="camelCase"
                key = col.name
                value = getattr(record, prop.key)
                data[key] = value

        data['id'] = record.id
        return data

    def find(self, entity_name: str, params: dict) -> dict:
        if self.user:
            level = acl_service.get_permission_level(self.user, entity_name, 'read')
            if level == 'no':
                raise PermissionError(f"Access denied for {entity_name}")

        model_class = self._get_model(entity_name)
        select_manager = SelectManager(self.db, model_class)

        if 'where' in params:
            select_manager.apply_where(params['where'])

        if self.user:
             self._apply_acl_filters(select_manager, entity_name, 'read')

        sort_by = params.get('sortBy')
        asc = params.get('asc', False)
        # EspoCRM uses 'asc' param which is boolean, defaulting to false (desc) usually?
        # Actually in Espo, 'asc' param is often 'true' or 'false' string, or absent.
        # But 'desc' param is also used in SelectManager PHP?
        # PHP code: order(string $sortBy, $desc, array &$result)
        # Check controller usage later. For now assume asc=True/False or "asc"/"desc" string?
        # Standard Espo API usually sends `sortBy` and `asc` (boolean).

        if sort_by:
             select_manager.apply_order(sort_by, asc)

        offset = params.get('offset')
        max_size = params.get('maxSize')

        if offset is not None:
            offset = int(offset)
        if max_size is not None:
            max_size = int(max_size)

        select_manager.apply_limit(offset, max_size)

        records, total = select_manager.execute()

        return {
            "list": [self._get_record_data(r) for r in records],
            "total": total
        }

    def create(self, entity_name: str, data: dict) -> dict:
        if self.user:
            if not acl_service.check(self.user, entity_name, 'create'):
                 raise PermissionError(f"Create access denied for {entity_name}")

        model_class = self._get_model(entity_name)
        record = model_class()

        record.id = generate_id()

        self._populate_record(record, data)

        # Set ownership if applicable
        if hasattr(record, 'assigned_user_id') and self.user:
            record.assigned_user_id = self.user.id

        self.db.add(record)
        self.db.flush() # Flush to get ID if needed for relationships

        # Handle team assignment
        team_ids = data.get('teamsIds')
        if team_ids is None and self.user and self.user.default_team_id:
             team_ids = [self.user.default_team_id]

        if team_ids:
             self._update_teams(record, team_ids, entity_name)

        self.db.commit()
        self.db.refresh(record)
        return self._get_record_data(record)

    def read(self, entity_name: str, id: str) -> Optional[dict]:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if not record:
            return None

        if self.user:
            if not acl_service.check_scope(self.user, record, 'read'):
                 raise PermissionError(f"Read access denied for {entity_name} {id}")

        return self._get_record_data(record)

    def update(self, entity_name: str, id: str, data: dict) -> Optional[dict]:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if not record:
            return None

        if self.user:
            if not acl_service.check_scope(self.user, record, 'edit'):
                 raise PermissionError(f"Edit access denied for {entity_name} {id}")

        self._populate_record(record, data)

        team_ids = data.get('teamsIds')
        if team_ids is not None:
             self._update_teams(record, team_ids, entity_name)

        self.db.commit()
        self.db.refresh(record)
        return self._get_record_data(record)

    def delete(self, entity_name: str, id: str) -> bool:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if record:
            if self.user:
                if not acl_service.check_scope(self.user, record, 'delete'):
                     raise PermissionError(f"Delete access denied for {entity_name} {id}")

            record.deleted = True
            self.db.commit()
            return True
        return False

    def link(self, entity_name: str, id: str, link_name: str, foreign_id: str) -> bool:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if not record:
            raise ValueError("Record not found")

        link_def = self._get_link_def(entity_name, link_name)
        link_type = link_def.get("type")
        related_entity = link_def.get("entity")

        foreign_model = self._get_model(related_entity)
        foreign_record = self.db.query(foreign_model).get(foreign_id)
        if not foreign_record:
             raise ValueError("Foreign record not found")

        # Find the attribute on the model that corresponds to 'link_name'
        # Try exact match first, then snake_case
        attr_name = self._find_attribute(record, link_name)

        if not attr_name:
             raise ValueError(f"Attribute for link {link_name} not found on model {entity_name}")

        if link_type == "belongsTo":
             setattr(record, attr_name, foreign_record)
        elif link_type in ["hasMany", "hasChildren"]:
             collection = getattr(record, attr_name)
             collection.append(foreign_record)
        else:
             raise ValueError(f"Unsupported link type: {link_type}")

        self.db.commit()
        return True

    def unlink(self, entity_name: str, id: str, link_name: str, foreign_id: str) -> bool:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if not record:
             raise ValueError("Record not found")

        link_def = self._get_link_def(entity_name, link_name)
        link_type = link_def.get("type")

        attr_name = self._find_attribute(record, link_name)
        if not attr_name:
             raise ValueError(f"Attribute for link {link_name} not found on model {entity_name}")

        if link_type == "belongsTo":
             setattr(record, attr_name, None)
        elif link_type in ["hasMany", "hasChildren"]:
             collection = getattr(record, attr_name)
             item_to_remove = next((x for x in collection if x.id == foreign_id), None)
             if item_to_remove:
                 collection.remove(item_to_remove)

        self.db.commit()
        return True

    def find_linked(self, entity_name: str, id: str, link_name: str) -> List[dict]:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if not record:
             raise ValueError("Record not found")

        attr_name = self._find_attribute(record, link_name)
        if not attr_name:
             raise ValueError(f"Attribute for link {link_name} not found on model {entity_name}")

        linked_records = getattr(record, attr_name)
        if not linked_records:
            return []

        # It could be a single record (belongsTo) or a list
        if not isinstance(linked_records, list) and not hasattr(linked_records, '__iter__'):
             # Single record
             return [self._get_record_data(linked_records)]

        return [self._get_record_data(r) for r in linked_records]

    def _populate_record(self, record, data):
        mapper = inspect(record).mapper
        for prop in mapper.iterate_properties:
            if hasattr(prop, 'columns'):
                col = prop.columns[0]
                # Try to find matching key in data
                val = None
                if col.name in data:
                    val = data[col.name]
                elif prop.key in data:
                    val = data[prop.key]

                if val is not None:
                    setattr(record, prop.key, val)

    def _get_link_def(self, entity_name, link_name):
        metadata = metadata_service.get_data()
        # Entity metadata is usually under 'entityDefs' -> {EntityName} -> ...
        # But MetadataService merges everything into top level keys.
        # Based on my check, 'Account' is a top level key in the merged metadata if it was loaded.
        # MetadataService loading:
        # Core metadata path: application/Espo/Resources/metadata
        #   entityDefs/User.json -> data['entityDefs']['User']? No.
        #   _load_from_subdir checks if directory 'entityDefs' exists in 'metadata'.
        #   Then for each file in entityDefs, it loads it.
        #   If file is User.json, key is 'User'.
        #   It puts it into section_data (which is data['entityDefs']).

        # So structure is data['entityDefs']['Account']...

        entity_defs = metadata.get('entityDefs', {}).get(entity_name, {})
        links = entity_defs.get('links', {})
        link = links.get(link_name)
        if not link:
             # Fallback: maybe it's in a module and structure is different?
             # MetadataService merges module metadata into the same structure.
             # Let's verify structure later. Assuming 'entityDefs' -> Entity -> links -> LinkName
             raise ValueError(f"Link {link_name} not found in metadata for {entity_name}")
        return link

    def _find_attribute(self, record, link_name):
        # Try exact match
        if hasattr(record, link_name):
            return link_name
        # Try snake_case
        sc = to_snake_case(link_name)
        if hasattr(record, sc):
            return sc
        return None

    def _apply_acl_filters(self, select_manager: SelectManager, entity_name: str, action: str):
        level = acl_service.get_permission_level(self.user, entity_name, action)
        if level == 'all':
            return
        if level == 'own':
            select_manager.apply_filter('assignedUserId', self.user.id)
        elif level == 'team':
             # Need to filter by team
             # Logic: record.teams IN user.teams OR record.assignedUserId = user.id
             # This requires SelectManager to support complex OR conditions or custom filter.
             # For now, let's implement a basic `apply_team_access_filter` in SelectManager.
             user_team_ids = [t.id for t in self.user.teams]
             select_manager.apply_team_access_filter(self.user.id, user_team_ids)

    def _update_teams(self, record, team_ids: List[str], entity_name: str):
        # Handle manual update of entity_team table because generic M2M with polymorphism is tricky
        from app.models.acl_entities import entity_team
        from sqlalchemy import delete

        # Check if model supports teams (has 'teams' relationship)
        if not hasattr(record, 'teams'):
             return

        # Delete existing links
        self.db.execute(
            delete(entity_team).where(
                entity_team.c.entity_id == record.id,
                entity_team.c.entity_type == entity_name
            )
        )

        # Insert new links
        if team_ids:
            values = [
                {"entity_id": record.id, "entity_type": entity_name, "team_id": t_id}
                for t_id in team_ids
            ]
            self.db.execute(entity_team.insert(), values)
