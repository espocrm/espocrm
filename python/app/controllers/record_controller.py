from fastapi import APIRouter, Depends, HTTPException, Body
from sqlalchemy.orm import Session
from typing import List, Optional, Any
from app.core.database import get_db
from app.services.record_service import RecordService

router = APIRouter()

def get_record_service(db: Session = Depends(get_db)):
    return RecordService(db)

@router.get("/Record/{entityName}/{id}")
def read_record(entityName: str, id: str, service: RecordService = Depends(get_record_service)):
    record = service.read(entityName, id)
    if not record:
        raise HTTPException(status_code=404, detail="Record not found")
    return record

@router.post("/Record/{entityName}")
def create_record(entityName: str, data: dict = Body(...), service: RecordService = Depends(get_record_service)):
    try:
        return service.create(entityName, data)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        # In production, log error and return generic 500
        print(e)
        raise HTTPException(status_code=500, detail=str(e))

@router.put("/Record/{entityName}/{id}")
def update_record(entityName: str, id: str, data: dict = Body(...), service: RecordService = Depends(get_record_service)):
    record = service.update(entityName, id, data)
    if not record:
        raise HTTPException(status_code=404, detail="Record not found")
    return record

@router.delete("/Record/{entityName}/{id}")
def delete_record(entityName: str, id: str, service: RecordService = Depends(get_record_service)):
    success = service.delete(entityName, id)
    if not success:
         raise HTTPException(status_code=404, detail="Record not found")
    return {"status": "success"}

# Relationship endpoints

@router.get("/Record/{entityName}/{id}/{linkName}")
def get_linked(entityName: str, id: str, linkName: str, service: RecordService = Depends(get_record_service)):
    try:
        # Returns a list of records or a collection object.
        # EspoCRM returns a collection wrapper usually. For now list is fine or we wrap it.
        records = service.find_linked(entityName, id, linkName)
        return {"list": records, "total": len(records)}
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

@router.post("/Record/{entityName}/{id}/{linkName}")
def link_record(entityName: str, id: str, linkName: str, body: dict = Body(...), service: RecordService = Depends(get_record_service)):
    # Body can contain 'id' (single) or 'ids' (multiple)
    try:
        if 'id' in body:
            service.link(entityName, id, linkName, body['id'])
        elif 'ids' in body:
            for foreign_id in body['ids']:
                service.link(entityName, id, linkName, foreign_id)
        else:
             raise HTTPException(status_code=400, detail="Missing id or ids in body")
        return {"status": "success"}
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))

@router.delete("/Record/{entityName}/{id}/{linkName}")
def unlink_record(entityName: str, id: str, linkName: str, body: dict = Body(...), service: RecordService = Depends(get_record_service)):
    try:
        if 'id' in body:
            service.unlink(entityName, id, linkName, body['id'])
        elif 'ids' in body:
             for foreign_id in body['ids']:
                service.unlink(entityName, id, linkName, foreign_id)
        else:
             raise HTTPException(status_code=400, detail="Missing id or ids in body")
        return {"status": "success"}
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
