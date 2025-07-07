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


class PersonInfo(
    UnmanagedDataclassModel,
    db_table="personinfo",
    unique_together=(("person_id", "data_key"),),
):
    person_id: int = field(models.IntegerField, primary_key=True)
    data_key: str = field(models.CharField, max_length=100, primary_key=True)
    data_value: str = field(models.TextField, default="")
    lastupdate: datetime.datetime = field(models.DateTimeField, auto_now=True)

    def __str__(self):
        return f"{self.person_id}: {self.data_key}"

    def str_data_value(self):
        if isinstance(self.data_value, str):
            return self.data_value
        elif isinstance(self.data_value, (int, float)):
            return str(self.data_value)
        else:
            raise ValueError(f"type: {type(self.data_value)} not supported")


class PolicyOrganization(UnmanagedDataclassModel, db_table="policyorganization"):
    id: Optional[int] = field(models.AutoField, primary_key=True)
    slug: str = field(models.CharField, max_length=255)
    name: str = field(models.CharField, max_length=255)
    classification: str = field(models.CharField, max_length=255)

    def __str__(self):
        return f"{self.slug}: {self.name}"


class PolicyComparisonPeriod(
    UnmanagedDataclassModel, db_table="policycomparisonperiod"
):
    id: Optional[int] = field(models.AutoField, primary_key=True)
    slug: str = field(models.CharField, max_length=255)
    description: str = field(models.TextField)
    start_date: datetime.date = field(models.DateField)
    end_date: datetime.date = field(models.DateField)
    chamber_id: int = field(models.IntegerField, default=0)

    def __str__(self):
        return f"{self.slug}: {self.description}"


class PolicyVoteDistribution(
    UnmanagedDataclassModel,
    db_table="policyvotedistribution",
    unique_together=(
        ("person_id", "policy_id", "period_id", "chamber_id", "party_id"),
    ),
):
    id: Optional[int] = field(models.AutoField, primary_key=True)
    policy_id: int = field(models.IntegerField, default=0)
    person_id: int = field(models.IntegerField, default=0)
    period_id: int = field(models.IntegerField, default=0)
    chamber_id: int = field(models.IntegerField, default=0)
    party_id: Optional[int] = field(models.IntegerField, blank=True, null=True)
    is_target: bool = field(models.BooleanField, default=False)
    num_votes_same: float = field(models.FloatField, default=0)
    num_strong_votes_same: float = field(models.FloatField, default=0)
    num_votes_different: float = field(models.FloatField, default=0)
    num_strong_votes_different: float = field(models.FloatField, default=0)
    num_votes_absent: float = field(models.FloatField, default=0)
    num_strong_votes_absent: float = field(models.FloatField, default=0)
    num_votes_abstain: float = field(models.FloatField, default=0)
    num_strong_votes_abstain: float = field(models.FloatField, default=0)
    num_agreements_same: float = field(models.FloatField, default=0)
    num_strong_agreements_same: float = field(models.FloatField, default=0)
    num_agreements_different: float = field(models.FloatField, default=0)
    num_strong_agreements_different: float = field(models.FloatField, default=0)
    start_year: int = field(models.IntegerField, default=0)
    end_year: int = field(models.IntegerField, default=0)
    distance_score: float = field(models.FloatField, default=0)

    def __str__(self):
        return f"{self.person_id}: {self.policy_id}"


class PolicyDivisionLink(
    UnmanagedDataclassModel,
    db_table="policydivisionlink",
    unique_together=(("policy_id", "division_id"),),
):
    id: Optional[int] = field(models.AutoField, primary_key=True)
    policy_id: str = field(models.CharField, max_length=100)
    division_id: str = field(models.CharField, max_length=100)
    direction: StrEnum = field(
        models.CharField,
        max_length=6,
        choices=[("agree", "Agree"), ("against", "Against"), ("neutral", "Neutral")],
        default="neutral",
    )
    strength: StrEnum = field(
        models.CharField,
        max_length=5,
        choices=[("weak", "Weak"), ("strong", "Strong")],
        default="weak",
    )
    lastupdate: datetime.datetime = field(models.DateTimeField, auto_now=True)

    def __str__(self):
        return f"{self.policy_id}: {self.division_id}"


class Policy(UnmanagedDataclassModel, db_table="policies"):
    policy_id: str = field(models.CharField, max_length=100, primary_key=True)
    title: str = field(models.TextField)
    description: str = field(models.TextField)
    image: str = field(models.CharField, max_length=200, blank=True, null=True)
    image_attrib: str = field(models.CharField, max_length=200, blank=True, null=True)
    image_license: str = field(models.CharField, max_length=200, blank=True, null=True)
    image_license_url: str = field(models.TextField, blank=True, null=True)
    image_source: str = field(models.TextField, blank=True, null=True)

    def __str__(self):
        return f"{self.policy_id}: {self.title}"


class Division(UnmanagedDataclassModel, db_table="divisions"):
    division_id: str = field(models.CharField, max_length=100, primary_key=True)
    house: str = field(models.CharField, max_length=100, blank=True, null=True)
    gid: str = field(models.CharField, max_length=100, default="")
    division_title: str = field(models.TextField)
    yes_text: str = field(models.TextField, blank=True, null=True)
    no_text: str = field(models.TextField, blank=True, null=True)
    division_date: datetime.date = field(
        models.DateField, default=datetime.date(1000, 1, 1)
    )
    division_number: int = field(models.IntegerField)
    yes_total: int = field(models.IntegerField, default=0)
    no_total: int = field(models.IntegerField, default=0)
    absent_total: int = field(models.IntegerField, default=0)
    both_total: int = field(models.IntegerField, default=0)
    majority_vote: StrEnum = field(
        models.CharField,
        max_length=3,
        choices=[("aye", "Aye"), ("no", "No"), ("", "Neutral")],
        default="",
    )
    lastupdate: datetime.datetime = field(models.DateTimeField, auto_now=True)
    title_priority: int = field(models.IntegerField, default=1)

    def __str__(self):
        return f"{self.division_id}: {self.division_title}"


class PersonDivisionVote(
    UnmanagedDataclassModel,
    db_table="persondivisionvotes",
    unique_together=(("person_id", "division_id"),),
):
    person_id: int = field(models.IntegerField, primary_key=True)
    division_id: str = field(models.CharField, max_length=100, primary_key=True)
    vote: StrEnum = field(
        models.CharField,
        max_length=8,
        choices=[
            ("yes", "Yes"),
            ("aye", "Aye"),
            ("no", "No"),
            ("both", "Both"),
            ("tellaye", "Tellaye"),
            ("tellno", "Tellno"),
            ("absent", "Absent"),
            ("spoiled", "Spoiled"),
        ],
    )
    proxy: Optional[int] = field(models.IntegerField, blank=True, null=True)
    lastupdate: datetime.datetime = field(models.DateTimeField, auto_now=True)

    def __str__(self):
        return f"{self.person_id}: {self.division_id}"


class VectorSearchSuggestions(
    UnmanagedDataclassModel, db_table="vector_search_suggestions"
):
    search_term: str = field(models.CharField, max_length=100, primary_key=True)
    search_suggestion: str = field(models.CharField, max_length=100)

    def __str__(self):
        return f"{self.search_term}: {self.search_suggestion}"
