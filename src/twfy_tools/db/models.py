"""
This is a simple one file setup for using django's ORM models.
"""

import datetime
from typing import Annotated, Optional

from django.db import models

from pydantic import StringConstraints
from pydantic.fields import Field as PydanticField

from twfy_tools.db import django_setup as django_setup
from twfy_tools.db.typed_model import TypedUnmanagedModel, field

from ..common.enum_backport import StrEnum

datetime_min = datetime.datetime(1, 1, 1, 0, 0, 0)

PrimaryKey = Annotated[Optional[int], models.AutoField(primary_key=True)]
CharField = Annotated[
    str, models.CharField(max_length=255), StringConstraints(max_length=255)
]
EmailField = Annotated[
    str,
    models.CharField(max_length=255),
    PydanticField(pattern=r"^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"),
]
TextField = Annotated[str, models.TextField()]
Len10CharField = Annotated[
    str, models.CharField(max_length=10), StringConstraints(max_length=10)
]
Len2CharField = Annotated[
    str, models.CharField(max_length=2), StringConstraints(max_length=2)
]
IntegerField = Annotated[int, models.IntegerField()]
PositiveIntegerField = Annotated[
    int, models.PositiveIntegerField(), PydanticField(gt=0)
]
DateTimeField = Annotated[datetime.datetime, models.DateTimeField()]
OptionalDateTimeField = Annotated[
    Optional[datetime.datetime], models.DateTimeField(null=True)
]


class UserLevels(StrEnum):
    VIEWER = "Viewer"
    USER = "User"
    MODERATOR = "Moderator"
    ADMINISTRATOR = "Administrator"
    SUPERUSER = "Superuser"


class OptinValues(StrEnum):
    OPTIN_SERVICE = "optin_service"
    OPTIN_STREAM = "optin_stream"
    OPTIN_ORG = "optin_org"
    NO_OPTIN = "optin_no"


class User(TypedUnmanagedModel, db_table="users"):
    UserLevels = UserLevels
    OptinValues = OptinValues

    user_id: PrimaryKey = None
    firstname: CharField = ""
    lastname: CharField = ""
    email: EmailField = ""
    password: str = field(models.CharField, max_length=102, default="")
    lastvisit: DateTimeField = datetime_min
    registrationtime: DateTimeField = datetime_min
    registrationip: str = field(models.CharField, max_length=20, blank=True, null=True)
    status: UserLevels = field(
        models.CharField,
        max_length=13,
        blank=True,
        null=True,
        default=UserLevels.VIEWER,
    )
    emailpublic: IntegerField = 0
    optin: IntegerField = 0
    deleted: IntegerField = 0
    postcode: Len10CharField = ""
    registrationtoken: str = field(models.CharField, max_length=24, default="")
    confirmed: IntegerField = 0
    url: CharField = ""
    api_key: str = field(
        models.CharField, unique=True, max_length=24, blank=True, null=True
    )
    facebook_id: str = field(models.CharField, max_length=24, blank=True, null=True)
    facebook_token: str = field(models.CharField, max_length=200, blank=True, null=True)

    def __str__(self):
        return f"{self.status}: {self.email}"

    def get_optin_values(self) -> list[OptinValues]:
        """
        Returns a list of OptinValues that match the user's optin value.
        """
        matched_values: list[OptinValues] = []
        if self.optin & 1:
            matched_values.append(OptinValues.OPTIN_SERVICE)
        if self.optin & 2:
            matched_values.append(OptinValues.OPTIN_STREAM)
        if self.optin & 4:
            matched_values.append(OptinValues.OPTIN_ORG)
        if not matched_values:
            matched_values.append(OptinValues.NO_OPTIN)
        return matched_values
