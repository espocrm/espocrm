import json
import os
from typing import Dict, Any, List

class LanguageService:
    def __init__(self):
        # Calculate the root of the repository relative to this file
        # This file is in python/app/services/
        # Root is ../../../
        self.root_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "../../../"))
        self.resource_path = os.path.join(self.root_path, "application/Espo/Resources/i18n")
        self.default_language = "en_US"

    def get_data_for_frontend(self, default: bool = False) -> Dict[str, Any]:
        """
        Reads language files and merges them.
        In a real scenario, this would check the user's preferences for language.
        For now, it defaults to en_US.
        """
        language = self.default_language
        # If default is True, we might want to ensure we return the system default.
        # But we are assuming system default is en_US for now.

        data = {}

        # 1. Load core language files
        core_path = os.path.join(self.resource_path, language)
        if os.path.exists(core_path):
            self._load_from_dir(core_path, data)

        # 2. Load module language files
        modules_path = os.path.join(self.root_path, "application/Espo/Modules")
        if os.path.exists(modules_path):
            self._load_from_modules(modules_path, language, data)

        # 3. Load custom language files
        custom_path = os.path.join(self.root_path, f"custom/Espo/Resources/i18n/{language}")
        if os.path.exists(custom_path):
            self._load_from_dir(custom_path, data)

        # Apply basic filtering (Mocking PHP logic)
        # In PHP, non-admin users have some data filtered out.
        # We are currently assuming Admin user in our mock endpoints, so we return everything.

        return data

    def _load_from_modules(self, modules_path: str, language: str, data: Dict[str, Any]):
        for module_name in os.listdir(modules_path):
            module_i18n_path = os.path.join(modules_path, module_name, "Resources", "i18n", language)
            if os.path.exists(module_i18n_path):
                self._load_from_dir(module_i18n_path, data)

    def _load_from_dir(self, directory: str, data: Dict[str, Any]):
        try:
            for filename in os.listdir(directory):
                if filename.endswith(".json"):
                    scope = filename[:-5] # remove .json
                    filepath = os.path.join(directory, filename)
                    try:
                        with open(filepath, 'r') as f:
                            content = json.load(f)

                            if scope not in data:
                                data[scope] = {}
                            self._deep_merge(data[scope], content)

                    except json.JSONDecodeError:
                        print(f"Error decoding {filepath}")
                    except Exception as e:
                        print(f"Error reading {filepath}: {e}")
        except Exception as e:
            print(f"Error listing directory {directory}: {e}")

    def _deep_merge(self, dict1, dict2):
        """
        Recursive merge of dict2 into dict1.
        """
        for key, value in dict2.items():
            if key in dict1 and isinstance(dict1[key], dict) and isinstance(value, dict):
                self._deep_merge(dict1[key], value)
            else:
                dict1[key] = value

language_service = LanguageService()
