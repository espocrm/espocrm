from fastapi import APIRouter, Depends, HTTPException
from typing import Optional

router = APIRouter()

# Mocking the service and metadata for now
class MetadataService:
    def get_data_for_frontend(self):
        return {
            "app": {
                "name": "EspoCRM Python",
                "version": "8.0.0"
            },
            "entityDefs": {},
            "scopes": {}
        }

    def get_data_for_frontend_by_key(self, key: str):
        # Mock logic
        return {"key": key, "value": "mock_value"}

metadata_service = MetadataService()

@router.get("/Metadata")
async def get_metadata(key: Optional[str] = None):
    if key:
        return metadata_service.get_data_for_frontend_by_key(key)
    return metadata_service.get_data_for_frontend()
