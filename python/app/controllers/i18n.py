from fastapi import APIRouter, Depends, Request
from app.services.language_service import language_service

router = APIRouter()

@router.get("/I18n")
async def get_i18n(request: Request):
    default = request.query_params.get('default') == 'true'
    return language_service.get_data_for_frontend(default)
