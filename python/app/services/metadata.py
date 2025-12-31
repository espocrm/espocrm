import json
import os
from typing import Dict, Any, List

class MetadataService:
    def __init__(self):
        # python/app/services/metadata.py -> ../../../
        self.root_path = os.path.abspath(os.path.join(os.path.dirname(__file__), "../../../"))
        self.core_metadata_path = os.path.join(self.root_path, "application/Espo/Resources/metadata")
        self.modules_path = os.path.join(self.root_path, "application/Espo/Modules")
        self.custom_path = os.path.join(self.root_path, "custom/Espo/Custom/Resources/metadata")
        self._data_cache = None

    def get_data(self) -> Dict[str, Any]:
        """
        Reads metadata files and merges them.
        Uses in-memory caching to avoid reading files on every request.
        """
        if self._data_cache is not None:
            return self._data_cache

        data = {}

        # 1. Load core metadata
        if os.path.exists(self.core_metadata_path):
            self._load_from_base_dir(self.core_metadata_path, data)

        # 2. Load module metadata
        if os.path.exists(self.modules_path):
            self._load_from_modules(data)

        # 3. Load custom metadata
        if os.path.exists(self.custom_path):
            self._load_from_base_dir(self.custom_path, data)

        self._data_cache = data
        return data

    def get_data_for_frontend(self) -> Dict[str, Any]:
        """
        Alias for get_data, matching the controller's expectation.
        In the future, this might apply frontend-specific filtering.
        """
        return self.get_data()

    def _load_from_modules(self, data: Dict[str, Any]):
        for module_name in os.listdir(self.modules_path):
            module_metadata_path = os.path.join(self.modules_path, module_name, "Resources", "metadata")
            if os.path.exists(module_metadata_path):
                self._load_from_base_dir(module_metadata_path, data)

    def _load_from_base_dir(self, base_path: str, data: Dict[str, Any]):
        """
        Loads metadata from a base directory which contains subdirectories like 'entityDefs', 'scopes', etc.
        """
        try:
            for dirname in os.listdir(base_path):
                dir_path = os.path.join(base_path, dirname)
                if os.path.isdir(dir_path):
                    if dirname not in data:
                        data[dirname] = {}
                    self._load_from_subdir(dir_path, data[dirname])
        except Exception as e:
            print(f"Error listing directory {base_path}: {e}")

    def _load_from_subdir(self, dir_path: str, section_data: Dict[str, Any]):
        """
        Loads JSON files from a specific section directory (e.g. entityDefs) and merges them into section_data.
        """
        try:
            for filename in os.listdir(dir_path):
                filepath = os.path.join(dir_path, filename)

                if os.path.isdir(filepath):
                    key = filename
                    if key not in section_data:
                        section_data[key] = {}

                    # Only recurse if the existing data is a dictionary.
                    # If it's a list (or other type), we can't merge a directory into it.
                    if isinstance(section_data[key], dict):
                        self._load_from_subdir(filepath, section_data[key])
                    continue

                if filename.endswith(".json"):
                    key = filename[:-5] # remove .json
                    try:
                        with open(filepath, 'r') as f:
                            content = json.load(f)

                            if content is None:
                                continue

                            if key not in section_data:
                                section_data[key] = content
                            else:
                                if isinstance(section_data[key], dict) and isinstance(content, dict):
                                    self._deep_merge(section_data[key], content)
                                else:
                                    # If not both dicts, overwrite (e.g. replacing a list or scalar)
                                    section_data[key] = content

                    except json.JSONDecodeError:
                        print(f"Error decoding {filepath}")
                    except Exception as e:
                        print(f"Error reading {filepath}: {e}")

        except Exception as e:
            print(f"Error listing directory {dir_path}: {e}")

    def _deep_merge(self, target: Dict[str, Any], source: Dict[str, Any]) -> None:
        """
        Recursive merge of source into target.
        """
        for key, value in source.items():
            if (
                key in target
                and isinstance(target[key], dict)
                and isinstance(value, dict)
            ):
                self._deep_merge(target[key], value)
            else:
                target[key] = value

metadata_service = MetadataService()
