import os
import uuid
import shutil
from fastapi import APIRouter, Depends, HTTPException, UploadFile, File, Form
from sqlalchemy.orm import Session
from typing import Optional
from ...core.deps import get_db, get_current_user
from ...models.attachment import Attachment
from ...models.user import User

router = APIRouter()

UPLOAD_DIR = "data/upload"

@router.post("/Attachment")
async def upload_attachment(
    file: UploadFile = File(...),
    parent_id: Optional[str] = Form(None),
    parent_type: Optional[str] = Form(None),
    role: Optional[str] = Form("Attachment"),
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user)
):
    if not os.path.exists(UPLOAD_DIR):
        os.makedirs(UPLOAD_DIR)

    attachment_id = str(uuid.uuid4())
    file_extension = os.path.splitext(file.filename)[1]
    storage_filename = f"{attachment_id}{file_extension}"
    file_path = os.path.join(UPLOAD_DIR, storage_filename)

    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    attachment = Attachment(
        id=attachment_id,
        name=file.filename,
        type=file.content_type,
        size=os.path.getsize(file_path),
        role=role,
        storage="Local",
        storage_file_path=storage_filename,
        parent_id=parent_id,
        parent_type=parent_type,
        created_by_id=user.id
    )

    db.add(attachment)
    db.commit()
    db.refresh(attachment)

    return attachment.to_dict()

@router.get("/Attachment/{id}")
async def get_attachment(
    id: str,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user)
):
    attachment = db.query(Attachment).filter(Attachment.id == id, Attachment.deleted == False).first()
    if not attachment:
        raise HTTPException(status_code=404, detail="Attachment not found")

    # Simple ACL: Check if user has access to parent or if it's global
    # For now, let's just return metadata.
    # Download would be another endpoint or a redirect.
    return attachment.to_dict()

@router.get("/Attachment/{id}/download")
async def download_attachment(
    id: str,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user)
):
    attachment = db.query(Attachment).filter(Attachment.id == id, Attachment.deleted == False).first()
    if not attachment:
        raise HTTPException(status_code=404, detail="Attachment not found")

    file_path = os.path.join(UPLOAD_DIR, attachment.storage_file_path)
    if not os.path.exists(file_path):
        raise HTTPException(status_code=404, detail="File not found on storage")

    from fastapi.responses import FileResponse
    return FileResponse(path=file_path, filename=attachment.name, media_type=attachment.type)
