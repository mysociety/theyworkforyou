#!.venv/bin/python

import os
from typing import Literal, Optional

import rich
from mysoc_validator.models.interests import RegmemPerson, RegmemRegister
from tqdm import tqdm
from typer import Typer
from typing_extensions import TypeGuard

from twfy_tools.common.config import config
from twfy_tools.db.utils import upload_person_info

app = Typer(pretty_exceptions_enable=False)


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


@app.callback()
def callback():
    pass


if __name__ == "__main__":
    app()
