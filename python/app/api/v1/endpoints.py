from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from app.controllers import metadata
from app.core.database import get_db

router = APIRouter()

@router.get("/")
async def get_api_index():
    return {"message": "API v1 root"}

@router.get("/App/user")
async def get_app_user(db: Session = Depends(get_db)):
    # Mocking user data that the frontend usually requests
    # In a real port, we would query the database here.
    return {
        "user": {
            "id": "1",
            "name": "Admin",
            "username": "admin",
            "isAdmin": True
        }
    }

router.include_router(metadata.router)
