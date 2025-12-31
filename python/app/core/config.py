import os
from typing import Any

class Config:
    def __init__(self):
        self._config = {
            "isInstalled": False, # Default to false
            "applicationName": "EspoCRM Python",
            "applicationDescription": "EspoCRM – Open Source CRM application.",
            "useCache": False,
            "isDeveloperMode": True,
            "cacheTimestamp": 0,
            "appTimestamp": 0,
            "ajaxTimeout": 60000,
            "clientSecurityHeadersDisabled": False,
            "clientCspDisabled": False,
            "clientCspFormActionDisabled": False,
            "clientXFrameOptionsHeaderDisabled": False,
            "clientStrictTransportSecurityHeaderDisabled": False,
            "siteUrl": "http://localhost:8000"
        }

    def get(self, key: str, default: Any = None) -> Any:
        return self._config.get(key, default)

    def set(self, key: str, value: Any):
        self._config[key] = value

    def is_installed(self) -> bool:
        return self.get("isInstalled")
