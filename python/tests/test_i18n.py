from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

def test_get_i18n():
    response = client.get("/api/v1/I18n")
    assert response.status_code == 200
    data = response.json()
    assert "Global" in data
    assert "User" in data
    assert "scopeNames" in data["Global"]
    assert "fields" in data["User"]
    assert data["User"]["fields"]["name"] == "Name"
