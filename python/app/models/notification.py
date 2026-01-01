from sqlalchemy import Column, String, DateTime, Boolean, ForeignKey, Text
from sqlalchemy.orm import relationship
from .base import Base
import datetime

class Notification(Base):
    __tablename__ = 'notification'

    id = Column(String(36), primary_key=True)
    message = Column(Text)
    type = Column(String(100))
    is_read = Column(Boolean, default=False)

    user_id = Column(String(36), ForeignKey('user.id'))
    user = relationship("User", foreign_keys=[user_id])

    related_id = Column(String(36))
    related_type = Column(String(100))

    created_at = Column(DateTime, default=datetime.datetime.utcnow)
    deleted = Column(Boolean, default=False)

    def to_dict(self):
        return {
            "id": self.id,
            "message": self.message,
            "type": self.type,
            "isRead": self.is_read,
            "userId": self.user_id,
            "relatedId": self.related_id,
            "relatedType": self.related_type,
            "createdAt": self.created_at.isoformat() if self.created_at else None
        }
