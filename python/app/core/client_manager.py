from typing import List, Dict, Any
import json
import time
import secrets
import os

class ClientManager:
    def __init__(self, config):
        self.config = config
        self.base_path = ""
        self.api_url = "api/v1"
        self.application_id = "espocrm"
        self.nonce = secrets.token_hex(16)
        # python/app/core/client_manager.py -> python/app/core/ -> python/app/ -> python/ -> root
        root_dir = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(__file__))))
        self.main_html_file_path = os.path.join(root_dir, "html", "main.html")

    def display(self) -> str:
        return self.render_internal()

    def render_internal(self) -> str:
        # Defaults
        run_script = "app.start();"

        cache_timestamp = self.config.get("cacheTimestamp")
        app_timestamp = self.config.get("appTimestamp")

        # Mocking metadata for now
        # TODO: Implement Metadata class

        scripts_html = ""
        # TODO: Get script list from metadata

        additional_style_sheets_html = ""
        links_html = ""

        internal_module_list = [] # TODO: Get from Module manager
        bundled_module_list = []

        # Stylesheet path seems to be different in this version or needs to be built.
        # Fallback to a known css file or empty for now if not found.
        stylesheet = "client/css/font-awesome.min.css" # Defaulting to FA as a placeholder

        theme = None

        loader_params = {
            "basePath": self.base_path,
            "cacheTimestamp": cache_timestamp if not self.config.get("isDeveloperMode") else None,
            "internalModuleList": internal_module_list,
            "transpiledModuleList": [],
            "libsConfig": {}, # TODO
            "aliasMap": {}    # TODO
        }

        data = {
            "applicationId": self.application_id,
            "apiUrl": self.api_url,
            "applicationName": self.config.get("applicationName"),
            "cacheTimestamp": cache_timestamp,
            "appTimestamp": app_timestamp,
            "loaderCacheTimestamp": json.dumps(loader_params["cacheTimestamp"]),
            "stylesheet": stylesheet,
            "theme": json.dumps(theme),
            "runScript": run_script,
            "basePath": self.base_path,
            "useCache": "true" if self.config.get("useCache") else "false",
            "appClientClassName": "app",
            "scriptsHtml": scripts_html,
            "additionalStyleSheetsHtml": additional_style_sheets_html,
            "linksHtml": links_html,
            "faviconAlternate": "client/img/favicon.ico",
            "favicon": "client/img/favicon.svg",
            "faviconType": "image/svg+xml",
            "ajaxTimeout": self.config.get("ajaxTimeout"),
            "internalModuleList": json.dumps(internal_module_list),
            "bundledModuleList": json.dumps(bundled_module_list),
            "applicationDescription": self.config.get("applicationDescription"),
            "nonce": self.nonce,
            "loaderParams": json.dumps(loader_params)
        }

        try:
            with open(self.main_html_file_path, "r") as f:
                html = f.read()

            for key, value in data.items():
                html = html.replace(f"{{{{{key}}}}}", str(value))

            return html
        except FileNotFoundError:
            # Fallback for when html/main.html is not found (e.g. testing environment)
            # In a real scenario, this should log an error.
            return f"<h1>Error: {self.main_html_file_path} not found</h1>"
