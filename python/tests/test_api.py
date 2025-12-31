from fastapi.testclient import TestClient
from app.main import app
from app.core.database import Base, engine, get_db
from app.models.user import User
from sqlalchemy.orm import sessionmaker
import pytest
import os

# Create a test database
TestingSessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

def override_get_db():
    try:
        db = TestingSessionLocal()
        yield db
    finally:
        db.close()

app.dependency_overrides[get_db] = override_get_db

client = TestClient(app)

@pytest.fixture(scope="module", autouse=True)
def setup_database():
    # Create tables
    Base.metadata.create_all(bind=engine)

    # Create test user
    db = TestingSessionLocal()
    if not db.query(User).filter(User.user_name == 'admin').first():
        user = User(id='1', user_name='admin', first_name='Admin', last_name='User', is_admin=True)
        db.add(user)
        db.commit()
    db.close()

    yield

    # We don't drop tables here to keep the state for manual verification if needed,
    # but in a real test suite we might want to cleanup.

def test_get_app_user():
    response = client.get("/api/v1/App/user")
    assert response.status_code == 200
    data = response.json()
    assert "user" in data
    assert data["user"]["username"] == "admin"
    assert data["user"]["isAdmin"] is True

def test_get_metadata():
    response = client.get("/api/v1/Metadata")
    assert response.status_code == 200
    data = response.json()
    assert "entityDefs" in data

def test_get_i18n():
    response = client.get("/api/v1/I18n")
    assert response.status_code == 200
    data = response.json()
    # "labels" is usually nested under "Global" or other entities in EspoCRM's I18n structure
    # But looking at the output, it seems we get a dictionary of entities.
    assert "Global" in data
    assert "labels" in data["Global"]
