from sqlalchemy import Column, String, Boolean, ForeignKey, Table, JSON
from sqlalchemy.orm import relationship
from app.core.database import Base

# Association tables
user_role = Table('user_role', Base.metadata,
    Column('user_id', String(24), ForeignKey('user.id')),
    Column('role_id', String(24), ForeignKey('role.id'))
)

team_role = Table('team_role', Base.metadata,
    Column('team_id', String(24), ForeignKey('team.id')),
    Column('role_id', String(24), ForeignKey('role.id'))
)

user_team = Table('user_team', Base.metadata,
    Column('user_id', String(24), ForeignKey('user.id')),
    Column('team_id', String(24), ForeignKey('team.id'))
)

# Generic Entity-Team link (EspoCRM uses separate link tables or a polymorphic one?
# Usually Espo uses `entity_team` table with `entity_id`, `entity_type`, `team_id`.
# But SQLAlchemy polymorphism with M2M is tricky.
# For standard entities like Account, Espo usually has `account_team` table or similar?
# Let's check Espo structure memory or online if needed.
# EspoCRM uses `entity_team` table: `entity_id`, `entity_type`, `team_id`.
# BUT SQLAlchemy `relationship` needs a specific association table or complex config.
# For simplicity in this migration, I will define `entity_team` table and mapped it manually or
# use specific association tables per entity if needed.
# Or I can use a single table and use `primaryjoin` / `secondaryjoin` conditions in relationship.

entity_team = Table('entity_team', Base.metadata,
    Column('entity_id', String(24), index=True),
    Column('entity_type', String(100), index=True),
    Column('team_id', String(24), ForeignKey('team.id'), index=True)
)

class Role(Base):
    __tablename__ = "role"
    id = Column(String(24), primary_key=True, index=True)
    name = Column(String(255))
    data = Column(JSON) # Permissions
    deleted = Column(Boolean, default=False)

class Team(Base):
    __tablename__ = "team"
    id = Column(String(24), primary_key=True, index=True)
    name = Column(String(255))
    deleted = Column(Boolean, default=False)

    roles = relationship("Role", secondary=team_role)
