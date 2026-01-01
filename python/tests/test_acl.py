import pytest
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.pool import StaticPool
from app.core.database import Base
from app.models.user import User
from app.models.acl_entities import Role, Team
from app.models.standard_entities import Account
from app.services.acl_service import acl_service
from app.services.record_service import RecordService

# Setup in-memory DB
engine = create_engine(
    "sqlite:///:memory:",
    connect_args={"check_same_thread": False},
    poolclass=StaticPool,
)
TestingSessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

@pytest.fixture
def db():
    Base.metadata.create_all(bind=engine)
    yield TestingSessionLocal()
    Base.metadata.drop_all(bind=engine)

def test_acl_service_merging(db):
    # Create Roles
    role_admin = Role(id="r1", name="Admin Role", data={"Account": {"read": "all", "edit": "all", "delete": "all", "create": "yes"}})
    role_team = Role(id="r2", name="Team Role", data={"Account": {"read": "team", "edit": "team", "delete": "team"}})
    role_own = Role(id="r3", name="Own Role", data={"Account": {"read": "own", "edit": "own", "delete": "own"}})
    role_no = Role(id="r4", name="No Role", data={"Account": {"read": "no", "edit": "no"}})

    db.add_all([role_admin, role_team, role_own, role_no])
    db.commit()

    # User with Own role
    user1 = User(id="u1", user_name="user1", is_admin=False)
    user1.roles.append(role_own)

    # User with Team role
    user2 = User(id="u2", user_name="user2", is_admin=False)
    user2.roles.append(role_team)

    # User with Mixed (Own + Team) -> Should be Team (more permissive)
    user3 = User(id="u3", user_name="user3", is_admin=False)
    user3.roles.append(role_own)
    user3.roles.append(role_team)

    # Admin User
    admin = User(id="u_admin", user_name="admin", is_admin=True)

    db.add_all([user1, user2, user3, admin])
    db.commit()

    # Check Permissions
    assert acl_service.get_permission_level(user1, "Account", "read") == "own"
    assert acl_service.get_permission_level(user2, "Account", "read") == "team"
    assert acl_service.get_permission_level(user3, "Account", "read") == "team" # Merged
    assert acl_service.get_permission_level(admin, "Account", "read") == "all"

    assert acl_service.check(user1, "Account", "create") == False # Default is no if not specified?
    # Role own data: {"read": "own", "edit": "own", "delete": "own"} -> create is missing -> 'no'

    # Add create permission to role_own
    role_own.data = {"Account": {"read": "own", "edit": "own", "delete": "own", "create": "yes"}}
    db.commit()

    # Re-fetch user to refresh relationship?
    db.refresh(user1)

    # Wait, Role.data is JSON, changing it in place might not trigger update if mutable dict?
    # SQLAlchemy handles JSON updates, but safer to re-assign.
    role_own.data = dict(role_own.data)
    db.add(role_own)
    db.commit()

    assert acl_service.check(user1, "Account", "create") == True

def test_acl_scope_check(db):
    # Roles
    role_own = Role(id="r1", name="Own Role", data={"Account": {"read": "own"}})
    role_team = Role(id="r2", name="Team Role", data={"Account": {"read": "team"}})
    db.add_all([role_own, role_team])

    # Teams
    team1 = Team(id="t1", name="Team 1")
    team2 = Team(id="t2", name="Team 2")
    db.add_all([team1, team2])

    # Users
    user1 = User(id="u1", user_name="user1") # Own role
    user1.roles.append(role_own)
    user1.teams.append(team1)

    user2 = User(id="u2", user_name="user2") # Team role
    user2.roles.append(role_team)
    user2.teams.append(team1)

    user3 = User(id="u3", user_name="user3") # Team role, different team
    user3.roles.append(role_team)
    user3.teams.append(team2)

    db.add_all([user1, user2, user3])
    db.commit()

    # Accounts
    acc1 = Account(id="a1", name="Acc 1", assigned_user_id="u1") # Owned by u1, no teams (implied team1?)
    # Espo: ownership doesn't imply team assignment automatically in DB, but logic might allow owner access.
    # Logic in check_scope: if level=team, owner access is allowed.

    acc2 = Account(id="a2", name="Acc 2", assigned_user_id="u2")
    # Add team1 to acc2
    # Standard entities: we added `teams` M2M. But it's complex to write to.
    # Let's bypass validation for test setup and use low-level insert?
    # Or just use `entity_team` table directly.
    from app.models.acl_entities import entity_team

    db.add_all([acc1, acc2])
    db.commit()

    db.execute(entity_team.insert().values(entity_id="a2", entity_type="Account", team_id="t1"))
    db.commit()

    # Reload accounts to see teams
    db.refresh(acc1)
    db.refresh(acc2)

    # Checks
    # User 1 (Own): Can read acc1 (owner). Cannot read acc2 (not owner).
    assert acl_service.check_scope(user1, acc1, "read") == True
    assert acl_service.check_scope(user1, acc2, "read") == False

    # User 2 (Team, Team 1): Can read acc1?
    # acc1 has no teams. user2 is in team1. acc1 owned by u1. u1 is in team1.
    # Does team permission include "records owned by users in my team"?
    # EspoCRM documentation: "Team: User can access records assigned to his/her teams."
    # It does NOT say "records owned by users in his/her teams". It's about the record's team assignment.
    # So if acc1 has no teams, user2 cannot see it unless user2 is owner.
    assert acl_service.check_scope(user2, acc1, "read") == False

    # Can read acc2? acc2 is in team1. user2 is in team1. Yes.
    assert acl_service.check_scope(user2, acc2, "read") == True

    # User 3 (Team, Team 2): Can read acc2? No, different team.
    assert acl_service.check_scope(user3, acc2, "read") == False

def test_record_service_acl(db):
    # Setup
    role_own = Role(id="r1", name="Own Role", data={"Account": {"read": "own", "create": "yes"}})
    db.add(role_own)

    user = User(id="u1", user_name="user1")
    user.roles.append(role_own)
    db.add(user)
    db.commit()

    service = RecordService(db, user)

    # Create
    data = service.create("Account", {"name": "My Account"})
    assert data["id"] is not None
    assert data["assignedUserId"] == "u1"

    # Read Own
    read_data = service.read("Account", data["id"])
    assert read_data is not None

    # Create another account owned by someone else
    other_acc = Account(id="other", name="Other", assigned_user_id="u2")
    db.add(other_acc)
    db.commit()

    # Read Other -> Should fail
    try:
        service.read("Account", "other")
        assert False, "Should raise PermissionError"
    except PermissionError:
        pass

    # Find (List) -> Should only return own
    result = service.find("Account", {})
    assert len(result["list"]) == 1
    assert result["list"][0]["id"] == data["id"]

def test_acl_fail_closed(db):
    """
    Test that if access level is 'own' or 'team' but the entity doesn't support it
    (no ownership/teams columns), it fails closed (returns nothing/access denied).
    """
    # Create Role with 'own' access to Role entity
    # Role entity does not have assignedUserId
    role_check_own = Role(id="r_own", name="Own Role", data={"Role": {"read": "own"}})
    db.add(role_check_own)

    user = User(id="u1", user_name="user1")
    user.roles.append(role_check_own)
    db.add(user)
    db.commit()

    service = RecordService(db, user)

    # Try to find Roles
    # Should return empty list because Role entity doesn't support 'own' check (no assignedUserId)
    # and SelectManager should fail closed.

    # Add a role to be found if check was lax
    other_role = Role(id="r_other", name="Other Role")
    db.add(other_role)
    db.commit()

    result = service.find("Role", {})
    assert len(result["list"]) == 0
