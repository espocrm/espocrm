from typing import Generator
from fastapi import Depends, HTTPException, status
from sqlalchemy.orm import Session
from app.core.database import get_db
from app.models.user import User

async def get_current_user(db: Session = Depends(get_db)) -> User:
    # Basic implementation of get_current_user for simulation
    # In a real app, this would validate a JWT token
    user = db.query(User).filter(User.user_name == 'admin').first()
    if not user:
        # Fallback to creating a mock user object if not in DB
        # Note: This is a bit hacky but helps skip auth for initial porting
        return User(id="1", user_name="admin", name="Admin", is_admin=True)
    return user
