"""
This is a simple one file setup for using django's ORM models.
"""

from __future__ import annotations

import datetime
from enum import IntEnum
from typing import Optional

from django.db import models

from twfy_tools.db import django_setup as django_setup

from ..common.enum_backport import StrEnum
from .model_helper import UnmanagedDataclassModel, field

datetime_min = datetime.datetime.min


class UserLevels(StrEnum):
    VIEWER = "Viewer"
    USER = "User"
    MODERATOR = "Moderator"
    ADMINISTRATOR = "Administrator"
    SUPERUSER = "Superuser"


class OptinValues(IntEnum):
    OPTIN_SERVICE = 1
    OPTIN_STREAM = 2
    OPTIN_ORG = 4


class User(UnmanagedDataclassModel, db_table="users"):
    user_id: Optional[int] = field(models.AutoField, primary_key=True)
    firstname: str = field(models.CharField, max_length=255, default="")
    lastname: str = field(models.CharField, max_length=255, default="")
    email: str = field(models.CharField, max_length=255)
    password: str = field(models.CharField, max_length=102, default="")
    lastvisit: datetime.datetime = field(models.DateTimeField, default=datetime_min)
    registrationtime: datetime.datetime = field(
        models.DateTimeField, default=datetime_min
    )
    registrationip: str = field(models.CharField, max_length=20, blank=True, null=True)
    status: UserLevels = field(
        models.CharField,
        max_length=13,
        blank=True,
        null=True,
        default=UserLevels.VIEWER,
    )
    emailpublic: int = field(models.IntegerField, default=0)
    optin: int = field(models.IntegerField, default=0)
    deleted: int = field(models.IntegerField, default=0)
    postcode: str = field(models.CharField, max_length=10, blank=True, null=True)
    registrationtoken: str = field(models.CharField, max_length=24, default="")
    confirmed: int = field(models.IntegerField, default=0)
    url: str = field(models.CharField, max_length=255, blank=True, null=True)
    api_key: str = field(
        models.CharField, unique=True, max_length=24, blank=True, null=True
    )
    facebook_id: str = field(models.CharField, max_length=24, blank=True, null=True)
    facebook_token: str = field(models.CharField, max_length=200, blank=True, null=True)

    UserLevels = UserLevels
    OptinValues = OptinValues

    def __str__(self):
        return f"{self.status}: {self.email}"

    def get_optin_values(self) -> list[OptinValues]:
        """
        Returns a list of OptinValues that match the user's optin value.
        """
        matched_values: list[OptinValues] = []
        for value in OptinValues:
            if self.optin & value:
                matched_values.append(value)
        return matched_values

    def add_optin(self, optin_value: OptinValues):
        """
        Add an optin value to the user.
        """
        self.optin |= optin_value

    def remove_optin(self, optin_value: OptinValues):
        """
        Remove an optin value from the user.
        """
        self.optin &= ~optin_value
