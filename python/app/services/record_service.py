from typing import Any, List, Optional
from sqlalchemy.orm import Session
from sqlalchemy import inspect
from app.models.user import User
from app.models.standard_entities import Account, Contact
from app.services.metadata import metadata_service
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
    def __init__(self, db: Session):
        self.db = db
        # Registry of models. In a real dynamic system, this would be more complex.
        self.models = {
            "User": User,
            "Account": Account,
            "Contact": Contact
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

    def create(self, entity_name: str, data: dict) -> dict:
        model_class = self._get_model(entity_name)
        record = model_class()

        record.id = generate_id()

        self._populate_record(record, data)

        self.db.add(record)
        self.db.commit()
        self.db.refresh(record)
        return self._get_record_data(record)

    def read(self, entity_name: str, id: str) -> Optional[dict]:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if not record:
            return None
        return self._get_record_data(record)

    def update(self, entity_name: str, id: str, data: dict) -> Optional[dict]:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if not record:
            return None

        self._populate_record(record, data)
        self.db.commit()
        self.db.refresh(record)
        return self._get_record_data(record)

    def delete(self, entity_name: str, id: str) -> bool:
        model = self._get_model(entity_name)
        record = self.db.query(model).get(id)
        if record:
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
