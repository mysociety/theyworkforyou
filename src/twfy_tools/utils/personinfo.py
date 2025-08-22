#!.venv/bin/python

import datetime
import os
from collections import defaultdict
from typing import Literal, Optional

import pandas as pd
import rich
from mysoc_validator.models.interests import RegmemPerson, RegmemRegister
from pydantic import BaseModel, RootModel, field_serializer, field_validator
from tqdm import tqdm
from typer import Typer
from typing_extensions import TypeGuard

from twfy_tools.common.config import config
from twfy_tools.db.utils import upload_person_info

appg_names_url = "https://pages.mysociety.org/appg-membership/data/appg_groups_and_memberships/latest/register.parquet"
appg_membership_url = "https://pages.mysociety.org/appg-membership/data/appg_groups_and_memberships/latest/members.parquet"
signatures_url = "https://votes.theyworkforyou.com/static/data/signatures.parquet"
statements_url = "https://votes.theyworkforyou.com/static/data/statements.parquet"

app = Typer(pretty_exceptions_enable=False)


class AppgDetails(BaseModel):
    slug: str
    title: str
    purpose: str
    website: Optional[str]
    source_url: str
    categories: Optional[list[str]]

    @field_validator("website", mode="before")
    def validate_website(cls, value):
        """
        If 'None' or empty string, return None
        """
        if not value:
            return None
        if value == "None":
            return None
        return value


class AppgMembership(BaseModel):
    appg: AppgDetails
    role: str
    membership_source_url: str


class APPGMembershipAssignment(BaseModel):
    is_officer_of: list[AppgMembership] = []
    is_ordinary_member_of: list[AppgMembership] = []


class Statement(BaseModel):
    title: str
    info_source: Optional[str]
    type: str
    id: int
    chamber_slug: str
    slug: str
    date: datetime.date
    total_signatures: int = 0

    @field_serializer("date")
    def serialize_date(self, dt: datetime.date, _info):
        return dt.isoformat()


class Signature(BaseModel):
    statement: Statement
    date: datetime.date

    @field_serializer("date")
    def serialize_date(self, dt: datetime.date, _info):
        return dt.isoformat()


class SignatureList(RootModel[list[Signature]]):
    def append(self, value):
        self.root.append(value)

    def truncate(self, value):
        self.root = self.root[value:]


def is_valid_language(lang: str) -> TypeGuard[Literal["en", "cy"]]:
    """
    Typeguard to convert confirm string in Literal["en", "cy"]
    """
    return lang in {"en", "cy"}


def prepare_chamber_regmem(
    chamber: str = "house-of-commons",
    *,
    language: Literal["en", "cy"] = "en",
    quiet: bool = False,
):
    """
    Extract the most recent register of interest for a person in this chamber and send for upload
    to the database.
    """
    if not quiet:
        rich.print(f"Processing {chamber} register of members' interests")

    source_path = config.RAWDATA / "scrapedjson" / "universal_format_regmem" / chamber

    if chamber == "senedd":
        source_path = source_path / language

    id_to_person: dict[int, RegmemPerson] = {}

    tqdm_disable = quiet
    # override if the standard environment variable is set
    if os.environ.get("TQDM_DISABLE"):
        tqdm_disable = True

    for file in tqdm(
        list(source_path.glob("*.json")),
        disable=tqdm_disable,
        desc="Processing sources",
    ):
        register = RegmemRegister.from_path(file)
        for person in register.persons:
            int_id = int(person.person_id.split("/")[-1])
            if int_id not in id_to_person:
                id_to_person[int_id] = person
            else:
                # check if we have a more modern version
                existing = id_to_person[int_id]
                if person.published_date > existing.published_date:
                    id_to_person[int_id] = person

    if not quiet:
        rich.print(f"Found [blue]{len(id_to_person)}[/blue] person items to upload")

    upload_person_info(
        f"person_regmem_{chamber}_{language}",
        id_to_person,
        remove_absent=True,
        quiet=quiet,
    )


@app.command()
def upload_all_regmem(quiet: bool = False):
    """
    Upload regmems for all known chambers
    """

    # english chambers
    chambers = [
        "house-of-commons",
        "scottish-parliament",
        "northern-ireland-assembly",
        "senedd",
    ]
    for chamber in chambers:
        prepare_chamber_regmem(chamber, quiet=quiet, language="en")

    # welsh chambers
    prepare_chamber_regmem("senedd", quiet=quiet, language="cy")


@app.command()
def upload_regmem(
    quiet: bool = False,
    all: bool = False,
    chamber: Optional[str] = None,
    language: str = "en",
):
    """
    Upload register of members' interests to the database for one chamber
    """

    if all:
        upload_all_regmem(quiet=quiet)
    else:
        if chamber is None:
            raise ValueError("You must specify a chamber if not uploading all")
        if is_valid_language(language):
            prepare_chamber_regmem(chamber, quiet=quiet, language=language)


@app.command()
def upload_enhanced_2024_regmem(quiet: bool = False):
    """
    Upload the results of the whofundsthem data.
    """
    source_path = (
        config.RAWDATA
        / "scrapedjson"
        / "universal_format_regmem"
        / "misc"
        / "enriched_register.json"
    )

    id_to_person: dict[int, RegmemPerson] = {}

    register = RegmemRegister.from_path(source_path)
    for person in register.persons:
        int_id = int(person.person_id.split("/")[-1])
        id_to_person[int_id] = person

    upload_person_info(
        "person_regmem_enriched2024_en",
        id_to_person,
        remove_absent=True,
        quiet=quiet,
        batch_size=100,
    )


@app.command()
def load_appg_membership(quiet: bool = False, include_ai_sources: bool = True):
    """
    Upload APPG membership information
    """
    appg_lookup = pd.read_parquet(appg_names_url)
    appg_lookup = appg_lookup.set_index("slug")

    id_to_person: defaultdict[int, APPGMembershipAssignment] = defaultdict(
        APPGMembershipAssignment
    )
    df = pd.read_parquet(appg_membership_url)
    # skip this by default while we validate the results
    if not include_ai_sources:
        df = df[df["source"] != "ai_search"]
    df = df.sort_values("appg")
    for _, row in df.iterrows():
        if pd.isna(row["twfy_id"]):
            continue

        int_id = int(row["twfy_id"].split("/")[-1])
        appg_details = appg_lookup.loc[row["appg"]]
        appg = AppgDetails(
            slug=row["appg"],
            title=appg_details["title"],
            purpose=appg_details["purpose"],
            website=appg_details["website"],
            source_url=appg_details["source_url"],
            categories=[],
        )
        if row["is_officer"] == 1:
            role = row["officer_role"]
            if pd.isna(role):
                role = ""
            membership = AppgMembership(
                appg=appg, role=role, membership_source_url=row["url_source"]
            )
            id_to_person[int_id].is_officer_of.append(membership)
        else:
            membership = AppgMembership(
                appg=appg, role="", membership_source_url=row["url_source"]
            )
            id_to_person[int_id].is_ordinary_member_of.append(membership)

    upload_person_info(
        "appg_membership",
        id_to_person,
        remove_absent=True,
        quiet=quiet,
    )


def make_signature_object(row, signature_counts):
    statement = Statement(
        title=row["title"],
        info_source=row["info_source"],
        type=row["type"],
        id=row["statement_id"],
        chamber_slug=row["chamber_slug"],
        slug=row["slug"],
        date=row["statement_date"],
        total_signatures=signature_counts.loc[row["statement_id"]]["num_signatures"],
    )
    signature = Signature(
        statement=statement,
        date=row["date"],
    )
    return signature


@app.command()
def load_statement_signatures(quiet: bool = False):
    """
    Upload signatures of EDMs and open letters
    """
    min_edm_date = datetime.date.today() - pd.offsets.DateOffset(months=3)
    min_edm_date = min_edm_date.date()

    min_letter_date = datetime.date.today() - pd.offsets.DateOffset(year=1)
    min_letter_date = min_letter_date.date()

    statements = pd.read_parquet(statements_url)
    statements = statements.set_index("id")
    statements = statements.rename(columns={"date": "statement_date"})

    edms = statements[
        (statements["type"] == "proposed_motion")
        & (statements["statement_date"] >= min_edm_date)
    ]
    letters = statements[
        (statements["type"] == "letter")
        & (statements["statement_date"] >= min_letter_date)
    ]

    df = pd.read_parquet(signatures_url)

    signature_counts = pd.pivot_table(
        df, values="person_id", index="statement_id", aggfunc="count"
    )
    signature_counts = signature_counts.rename(columns={"person_id": "num_signatures"})

    edms = edms.merge(df, left_on="id", right_on="statement_id")
    letters = letters.merge(df, left_on="id", right_on="statement_id")

    edm_id_to_person = {}
    letter_id_to_person = {}

    for _, row in edms.iterrows():
        details = make_signature_object(row, signature_counts)
        if edm_id_to_person.get(row["person_id"]) is None:
            edm_id_to_person[row["person_id"]] = SignatureList(
                [
                    details,
                ]
            )
        else:
            edm_id_to_person[row["person_id"]].append(details)

    for _, row in letters.iterrows():
        details = make_signature_object(row, signature_counts)
        if letter_id_to_person.get(row["person_id"]) is None:
            letter_id_to_person[row["person_id"]] = SignatureList(
                [
                    details,
                ]
            )
        else:
            letter_id_to_person[row["person_id"]].append(details)

    # restrict to the last 10 because some people sign a lot of things
    for person_id in edm_id_to_person.keys():
        edm_id_to_person[person_id].truncate(-10)

    upload_person_info(
        "edms_signed",
        edm_id_to_person,
        remove_absent=True,
        quiet=quiet,
    )

    upload_person_info(
        "letters_signed",
        letter_id_to_person,
        remove_absent=True,
        quiet=quiet,
    )


@app.callback()
def callback():
    pass


if __name__ == "__main__":
    app()
