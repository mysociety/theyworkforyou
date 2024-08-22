from pathlib import Path
from typing import Any, Callable

from pydantic import BaseModel
from pylib.mysociety import config as base_config

BaseConfigGet = Callable[[str], Any]

repository_path = Path(__file__).resolve().parents[3]

base_config.set_file(repository_path / "conf" / "general")


class ConfigModel(BaseModel):
    """
    Shortcut to reveal to IDE the structure of the configuration.
    """

    TWFY_DB_HOST: str
    TWFY_DB_NAME: str
    TWFY_DB_USER: str
    TWFY_DB_PASS: str
    TWFY_DB_PORT: str
    RAWDATA: Path
    PWMEMBERS: Path

    @classmethod
    def from_php_config(cls, php_config_get: BaseConfigGet):
        # iterate over the fields of the model
        # and get the value from the php config

        items = {}

        for field in cls.model_fields.keys():
            try:
                items[field] = php_config_get(field)
            except Exception as e:
                raise ValueError(f"Error getting {field} from php config: {e}")

        return cls(**items)


config = ConfigModel.from_php_config(base_config.get)
