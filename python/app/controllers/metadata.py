from fastapi import APIRouter, Depends, HTTPException
from typing import Optional
from app.services.metadata import metadata_service

router = APIRouter()

@router.get("/Metadata")
async def get_metadata(key: Optional[str] = None):
    # In EspoCRM, ?key=... parameter is sometimes used to fetch specific parts, but usually the whole metadata is fetched.
    # If key is provided, we might need to filter.
    # The PHP Metadata controller doesn't seem to expose a simple 'key' param for partial fetch in the main action,
    # but let's keep the mock signature for now.

    data = metadata_service.get_data_for_frontend()

    if key:
        # Simple support for top-level keys like 'entityDefs'
        if key in data:
            return data[key]
        else:
            return {} # Or 404? Espo usually returns empty or null if not found.

    return data
