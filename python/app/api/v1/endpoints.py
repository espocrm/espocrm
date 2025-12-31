from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from app.controllers import metadata, i18n
from app.core.database import get_db
from app.models.user import User

router = APIRouter()

@router.get("/")
async def get_api_index():
    return {"message": "API v1 root"}

@router.get("/App/user")
async def get_app_user(db: Session = Depends(get_db)):
    # In a real scenario, we would determine the current user from the session or token.
    # For now, since we don't have authentication middleware yet, we will fetch the first admin user
    # or a specific user to simulate a logged-in state.

    # Try to find 'admin' user or any user.
    user = db.query(User).filter(User.user_name == 'admin').first()
    if not user:
         user = db.query(User).first()

    if user:
        return {
            "user": {
                "id": user.id,
                "name": user.name,
                "username": user.user_name,
                "isAdmin": user.is_admin
            }
        }
    else:
        # Fallback if no user exists in DB yet (e.g. fresh install)
        # This is strictly for development convenience
        return {
            "user": {
                "id": "1",
                "name": "Admin",
                "username": "admin",
                "isAdmin": True
            }
        }

router.include_router(metadata.router)
router.include_router(i18n.router)
