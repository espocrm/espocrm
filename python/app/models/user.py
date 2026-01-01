from sqlalchemy import Column, String, Boolean, Integer, ForeignKey
from sqlalchemy.orm import relationship
from app.core.database import Base
from app.models.acl_entities import user_role, user_team, Role, Team

class User(Base):
    __tablename__ = "user"

    id = Column(String(24), primary_key=True, index=True)
    user_name = Column("userName", String(255), unique=True, index=True)
    first_name = Column("firstName", String(255))
    last_name = Column("lastName", String(255))
    is_admin = Column("isAdmin", Boolean, default=False)
    default_team_id = Column("defaultTeamId", String(24), ForeignKey("team.id"), nullable=True)

    roles = relationship("Role", secondary=user_role)
    teams = relationship("Team", secondary=user_team)

    # We might need to add other fields that EspoCRM expects, but these are the basics.
    # The frontend expects 'name', 'username', 'isAdmin', 'id'.
    # 'name' is usually a computed property or concatenation of first and last name.

    @property
    def name(self):
        if self.first_name and self.last_name:
            return f"{self.first_name} {self.last_name}"
        elif self.last_name:
            return self.last_name
        elif self.first_name:
            return self.first_name
        return self.user_name
