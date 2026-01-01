from fastapi import FastAPI, Request
from fastapi.staticfiles import StaticFiles
from fastapi.responses import HTMLResponse
import os
import sys

# Add python directory to sys.path to import modules
# This allows imports like 'from app.core...' to work when running from python/ directory
python_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if python_dir not in sys.path:
    sys.path.append(python_dir)

from app.core.config import Config
from app.core.client_manager import ClientManager
from app.api.v1 import endpoints
from app.core.database import engine
from app.models.base import Base
# Import models so they are registered with Base
from app.models import user, attachment, notification, acl_entities, standard_entities

Base.metadata.create_all(bind=engine)

app = FastAPI()

# Serve static files from the client directory
# The original PHP app aliases /client/ to the client/ directory.
# We assume we are running from python/ directory, so client is at ../client
client_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.dirname(__file__))), "client")
app.mount("/client", StaticFiles(directory=client_dir), name="client")

config = Config()
client_manager = ClientManager(config)

app.include_router(endpoints.router, prefix="/api/v1")

@app.get("/")
async def read_root():
    # In PHP, this runs Client.php runner.
    html_content = client_manager.display()
    return HTMLResponse(content=html_content)
