from sqlalchemy import Column, String, ForeignKey, Table, Boolean, and_
from sqlalchemy.orm import relationship
from app.core.database import Base
from app.models.user import User
from app.models.acl_entities import entity_team, Team

# Many-to-Many association table
account_contact = Table('account_contact', Base.metadata,
    Column('account_id', String(24), ForeignKey('account.id')),
    Column('contact_id', String(24), ForeignKey('contact.id')),
    Column('role', String(100)),
    Column('is_inactive', Boolean, default=False),
    Column('deleted', Boolean, default=False)
)

class Account(Base):
    __tablename__ = "account"

    id = Column(String(24), primary_key=True, index=True)
    name = Column(String(255))
    website = Column(String(255))
    email_address = Column("emailAddress", String(255))
    type = Column(String(100))
    industry = Column(String(100))
    description = Column(String)

    deleted = Column(Boolean, default=False)

    assigned_user_id = Column("assignedUserId", String(24), ForeignKey("user.id"))
    assigned_user = relationship("User")

    teams = relationship("Team", secondary=entity_team,
                         primaryjoin="and_(Account.id==entity_team.c.entity_id, entity_team.c.entity_type=='Account')",
                         secondaryjoin="Team.id==entity_team.c.team_id",
                         viewonly=True)
    # Note: Implementing writable M2M with polymorphic association table in SQLAlchemy is complex.
    # For now, viewonly=True allows reading teams. Writing might need explicit handling or a custom Association Proxy.
    # Actually, for this migration, let's keep it simple.
    # EspoCRM usually manages entity_team manually or via specific tables.
    # If I want to filter by teams, I just need to be able to join.

    # Relationships
    contacts = relationship("Contact", secondary=account_contact, back_populates="accounts")
    # One-to-Many (Contact.account -> Account.contactsPrimary)
    contacts_primary = relationship("Contact", back_populates="account")

class Contact(Base):
    __tablename__ = "contact"

    id = Column(String(24), primary_key=True, index=True)
    first_name = Column("firstName", String(100))
    last_name = Column("lastName", String(100))
    email_address = Column("emailAddress", String(255))
    phone_number = Column("phoneNumber", String(255))
    title = Column(String(100))
    description = Column(String)

    deleted = Column(Boolean, default=False)

    assigned_user_id = Column("assignedUserId", String(24), ForeignKey("user.id"))
    assigned_user = relationship("User")

    teams = relationship("Team", secondary=entity_team,
                         primaryjoin="and_(Contact.id==entity_team.c.entity_id, entity_team.c.entity_type=='Contact')",
                         secondaryjoin="Team.id==entity_team.c.team_id",
                         viewonly=True)

    # One-to-Many (Contact belongs to one primary Account)
    account_id = Column("accountId", String(24), ForeignKey("account.id"))
    account = relationship("Account", back_populates="contacts_primary")

    # Many-to-Many
    accounts = relationship("Account", secondary=account_contact, back_populates="contacts")
