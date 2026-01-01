from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from ...core.deps import get_db, get_current_user
from ...models.notification import Notification
from ...models.user import User

router = APIRouter()

@router.get("/Notification")
async def get_notifications(
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user)
):
    notifications = db.query(Notification).filter(
        Notification.user_id == user.id,
        Notification.deleted == False
    ).order_by(Notification.created_at.desc()).limit(50).all()

    return {
        "list": [n.to_dict() for n in notifications],
        "total": len(notifications)
    }

@router.patch("/Notification/{id}")
async def mark_notification_as_read(
    id: str,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user)
):
    notification = db.query(Notification).filter(
        Notification.id == id,
        Notification.user_id == user.id,
        Notification.deleted == False
    ).first()

    if not notification:
        raise HTTPException(status_code=404, detail="Notification not found")

    notification.is_read = True
    db.commit()
    db.refresh(notification)

    return notification.to_dict()

@router.post("/Notification/action/markAllAsRead")
async def mark_all_as_read(
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user)
):
    db.query(Notification).filter(
        Notification.user_id == user.id,
        Notification.is_read == False
    ).update({Notification.is_read: True}, synchronize_session=False)

    db.commit()
    return {"status": "success"}
