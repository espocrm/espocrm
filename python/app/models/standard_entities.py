from sqlalchemy import Column, String, ForeignKey, Table, Boolean
from sqlalchemy.orm import relationship
from app.core.database import Base
from app.models.user import User

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

    # One-to-Many (Contact belongs to one primary Account)
    account_id = Column("accountId", String(24), ForeignKey("account.id"))
    account = relationship("Account", back_populates="contacts_primary")

    # Many-to-Many
    accounts = relationship("Account", secondary=account_contact, back_populates="contacts")
