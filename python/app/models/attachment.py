from sqlalchemy import Column, String, Integer, DateTime, Boolean, ForeignKey, BigInteger
from sqlalchemy.orm import relationship
from .base import Base
import datetime

class Attachment(Base):
    __tablename__ = 'attachment'

    id = Column(String(36), primary_key=True)
    name = Column(String(255), nullable=False)
    type = Column(String(100))
    size = Column(BigInteger)
    role = Column(String(36))
    storage = Column(String(24))
    storage_file_path = Column(String(260))
    global_ = Column('global', Boolean, default=False)

    parent_id = Column(String(36))
    parent_type = Column(String(100))

    deleted = Column(Boolean, default=False)
    created_at = Column(DateTime, default=datetime.datetime.utcnow)
    modified_at = Column(DateTime, default=datetime.datetime.utcnow, onupdate=datetime.datetime.utcnow)
    created_by_id = Column(String(36), ForeignKey('user.id'))

    created_by = relationship("User", foreign_keys=[created_by_id])

    def to_dict(self):
        return {
            "id": self.id,
            "name": self.name,
            "type": self.type,
            "size": self.size,
            "role": self.role,
            "parent_id": self.parent_id,
            "parent_type": self.parent_type,
            "createdAt": self.created_at.isoformat() if self.created_at else None,
            "createdById": self.created_by_id
        }
