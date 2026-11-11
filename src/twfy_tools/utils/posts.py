"""
Import committee and post memberships from the parlparse members/posts popolo
files into the organization and moffice tables.

There is one file per parliament. Each holds the committees of that parliament
as organizations, and memberships that are either a seat on one of those
committees or a role held directly in the chamber (a minister, a whip, an
opposition spokesperson).

Membership person_id references the main people file;
organization_id links committee memberships to the organizations in this file.
Localized names, descriptions and roles live in extra.localised_values.
Organization extra.tags supplies committee categories, including public bill
committees.
"""

from __future__ import annotations

import datetime
from pathlib import Path
from typing import NamedTuple

from django.db import transaction

import rich
from mysoc_validator import Popolo
from mysoc_validator.models.dates import ApproxDate
from mysoc_validator.models.popolo import Membership
from mysoc_validator.models.popolo import Organization as PopoloOrganization
from mysoc_validator.models.popolo_extras import Language
from pydantic import BaseModel, Field
from typer import Typer

from twfy_tools.common.config import config
from twfy_tools.common.text import slugify
from twfy_tools.db.models import Moffice, Organization
from twfy_tools.db.table_sync import sync_table

app = Typer(pretty_exceptions_enable=False)

PARLIAMENT_FILES = {
    "uk": "westminster-parliament-posts.json",
    "scotland": "scottish-parliament-committees.json",
    "wales": "senedd-committees.json",
    "northern-ireland": "ni-assembly-committees.json",
}

# Import English and any explicit Welsh translations supplied by the Senedd.
# Not every record or field necessarily has a translation.
LANGUAGES: list[Language] = ["en", "cy"]

# Roles held directly in a chamber rather than on a committee. Anything the
# lookup misses is a devolved government role - the devolved files only use
# chamber memberships for ministerial posts.
SOURCE_POST_TYPES = {
    "datadotparl/governmentpost": "government",
    "datadotparl/oppositionpost": "opposition",
    "datadotparl/parliamentarypost": "parliamentary",
}

# MySQL dates start here, and popolo uses year 1 for "no start date known".
MIN_DATE = datetime.date(1000, 1, 1)
MAX_DATE = datetime.date(9999, 12, 31)

ORGANIZATION_FIELDS = [
    "parliament",
    "classification",
    "slug",
    "name",
    "description",
    "url",
    "tags",
    "parent_org_id",
    "loader",
]

MOFFICE_FIELDS = [
    "dept",
    "position",
    "position_cy",
    "from_date",
    "to_date",
    "person",
    "source",
    "org_id",
    "post_type",
    "parliament",
    "loader",
]


class ParliamentSnapshot(NamedTuple):
    """
    An authoritative snapshot, including the loader whose rows it replaces.
    """

    parliament: str
    loader: str
    organizations: list[Organization]
    offices: list[Moffice]


class OrganizationTags(BaseModel):
    """
    Optional committee categories stored in Popolo organization extras.
    """

    tags: list[str] | None = Field(default_factory=list)


def has_language(item: Membership | PopoloOrganization, language: Language) -> bool:
    """
    Check for explicit translations, without the canonical-field fallback.
    """
    if item.extra is None:
        return False
    return any(language in values for values in item.extra.localised_values.values())


def translated_role(membership: Membership, language: Language) -> str:
    """
    Return an explicit role translation, leaving absent translations empty.
    """
    if membership.extra is None:
        return ""
    return membership.extra.localised_values.get("role", {}).get(language, "")


def organization_tags(org: PopoloOrganization) -> str:
    """
    Read committee categories consistently across all parliaments.

    A file without tags needs no parliament-specific exception.
    """
    extra = org.get_extra_as(OrganizationTags)
    return ",".join(extra.tags or []) if extra else ""


def organization_url(org: PopoloOrganization, language: Language) -> str:
    """
    Pick a senedd.cymru link for Welsh, otherwise prefer the English link.
    If no link matches that preference, retain the first available source link.
    """
    if not org.links:
        return ""
    welsh = [link for link in org.links if "senedd.cymru" in link]
    other = [link for link in org.links if "senedd.cymru" not in link]
    if language == "cy" and welsh:
        return welsh[0]
    if other:
        return other[0]
    return org.links[0]


def build_organizations(
    popolo: Popolo, parliament: str, loader: str
) -> list[Organization]:
    """
    One row per organisation per language it has values for.
    """
    rows: list[Organization] = []
    used_slugs: dict[tuple[str, str], set[str]] = {}

    for org in sorted(popolo.organizations, key=lambda o: o.id):
        languages: list[Language] = ["en"] + [
            language
            for language in LANGUAGES
            if language != "en" and has_language(org, language=language)
        ]
        for language in languages:
            name = org.get_localised_value("name", language=language) or org.name
            description = (
                org.get_localised_value("description", language=language)
                or org.description
                or ""
            )
            slug = unique_slug(
                slugify(name), used=used_slugs.setdefault((language, parliament), set())
            )
            rows.append(
                Organization(
                    org_id=org.id,
                    language=language,
                    parliament=parliament,
                    classification=org.classification or "",
                    slug=slug,
                    name=name,
                    description=description,
                    url=organization_url(org, language=language),
                    tags=organization_tags(org),
                    parent_org_id=org.parent_id,
                    loader=loader,
                )
            )
    return rows


def unique_slug(slug: str, used: set[str]) -> str:
    """
    Slugs are unique per language and parliament. A handful of committees
    slugify to the same string, so number the later ones.
    """
    candidate = slug
    suffix = 1
    while candidate in used:
        suffix += 1
        candidate = f"{slug}-{suffix}"
    used.add(candidate)
    return candidate


def as_date(
    value: datetime.date | ApproxDate | None, default: datetime.date
) -> datetime.date:
    """
    Convert Popolo dates to the schema's MySQL-compatible sentinels.

    Preserve the existing earliest-bound policy for partial dates, including
    end dates. Popolo's year 1 sentinel becomes the supplied database default.
    """
    match value:
        case ApproxDate():
            date = value.earliest_date
        case datetime.date():
            date = value
        case None:
            return default
        case _:
            raise TypeError(f"Unsupported date type: {type(value).__name__}")
    if date < MIN_DATE:
        return default
    return datetime.date(date.year, date.month, date.day)


def build_office(
    membership: Membership,
    org: PopoloOrganization | None,
    parliament: str,
    loader: str,
) -> Moffice:
    """
    A membership either sits on a committee in this file, or is a role held in
    the chamber itself. Chamber roles get an empty dept so that
    prettify_office() shows the role on its own, as the old Perl loader did for
    the House of Commons. Popolo role is stored in the existing position column;
    position_cy is the deliberate Welsh exception to that legacy schema.
    """
    position = membership.get_localised_value("role", language="en") or membership.role
    source = membership.source or ""
    if org is not None:
        dept = org.get_localised_value("name", language="en") or org.name
        org_id = org.id
        post_type = "committee"
    else:
        dept = ""
        org_id = None
        post_type = SOURCE_POST_TYPES.get(source, "government")

    return Moffice(
        moffice_id=membership.id,
        dept=dept,
        position=position or "Member",
        position_cy=translated_role(membership, language="cy"),
        from_date=as_date(membership.start_date, default=MIN_DATE),
        to_date=as_date(membership.end_date, default=MAX_DATE),
        person=int(membership.person_id.split("/")[-1]),
        source=source,
        org_id=org_id,
        post_type=post_type,
        parliament=parliament,
        loader=loader,
    )


def build_offices(
    popolo: Popolo, parliament: str, loader: str, quiet: bool = False
) -> list[Moffice]:
    """
    Convert direct committee and chamber memberships, reporting skipped posts.
    """
    file_orgs = {org.id: org for org in popolo.organizations}
    rows: list[Moffice] = []
    skipped = 0

    for membership in popolo.memberships:
        if membership.post_id:
            # Northern Ireland ministerial roles hang off a post rather than an
            # organisation, and need the department out of the post. Not
            # handled yet.
            skipped += 1
            continue
        org = file_orgs.get(membership.organization_id or "")
        rows.append(
            build_office(membership, org=org, parliament=parliament, loader=loader)
        )

    if skipped and not quiet:
        rich.print(
            f"[yellow]Skipped {skipped} {parliament} memberships held through a post[/yellow]"
        )
    return rows


def read_parliament(parliament: str, quiet: bool = False) -> ParliamentSnapshot | None:
    """
    Read one parliament's file, or return None if it isn't in the checkout yet.
    """
    path = config.PWMEMBERS / "posts" / PARLIAMENT_FILES[parliament]
    if not path.exists():
        rich.print(f"[yellow]No file for {parliament} at {path}, skipping[/yellow]")
        return None

    loader = f"posts-{parliament}"
    popolo = Popolo.from_path(path, cross_validate=False)
    organizations = build_organizations(popolo, parliament=parliament, loader=loader)
    offices = build_offices(popolo, parliament=parliament, loader=loader, quiet=quiet)
    if not quiet:
        rich.print(
            f"Read [blue]{len(organizations)}[/blue] organizations and "
            f"[blue]{len(offices)}[/blue] memberships for {parliament}"
        )
    return ParliamentSnapshot(
        parliament=parliament,
        loader=loader,
        organizations=organizations,
        offices=offices,
    )


@transaction.atomic
def store(
    organizations: list[Organization],
    offices: list[Moffice],
    loaders: list[str],
    quiet: bool = False,
) -> None:
    """
    Sync both tables. Rows an older loader wrote under the same id are taken
    over; only rows owned by these successfully read loaders are deleted.
    Empty snapshots delete those loaders' rows; missing files must not supply
    a loader. Both tables and language partitions are updated atomically.
    """
    for language in LANGUAGES:
        result = sync_table(
            Organization,
            [org for org in organizations if org.language == language],
            key_field="org_id",
            update_fields=ORGANIZATION_FIELDS,
            owned={"loader__in": loaders},
            partition={"language": language},
        )
        if not quiet:
            rich.print(f"organization ({language}): {result}")

    result = sync_table(
        Moffice,
        offices,
        key_field="moffice_id",
        update_fields=MOFFICE_FIELDS,
        owned={"loader__in": loaders},
    )
    if not quiet:
        rich.print(f"moffice: {result}")


@app.command()
def load_all(quiet: bool = False) -> None:
    """
    Load available parliament snapshots, preserving rows for missing files.

    A present, valid empty file is authoritative and removes its owned rows.
    """
    organizations: list[Organization] = []
    offices: list[Moffice] = []
    loaders: list[str] = []

    for parliament in PARLIAMENT_FILES:
        read = read_parliament(parliament, quiet=quiet)
        if read is None:
            continue
        organizations.extend(read.organizations)
        offices.extend(read.offices)
        loaders.append(read.loader)

    if not loaders:
        rich.print("[red]No posts files found, nothing to load[/red]")
        return

    store(
        organizations,
        offices=offices,
        loaders=loaders,
        quiet=quiet,
    )


@app.command()
def load_parliament(parliament: str, quiet: bool = False) -> None:
    """
    Load a single parliament.
    """
    if parliament not in PARLIAMENT_FILES:
        raise ValueError(
            f"Unknown parliament {parliament}, expected one of "
            f"{', '.join(PARLIAMENT_FILES)}"
        )
    read = read_parliament(parliament, quiet=quiet)
    if read is None:
        return
    store(read.organizations, offices=read.offices, loaders=[read.loader], quiet=quiet)


@app.command()
def load_file(path: Path, parliament: str, quiet: bool = False) -> None:
    """
    Load an arbitrary posts file as the given parliament.
    """
    if parliament not in PARLIAMENT_FILES:
        raise ValueError(f"Unknown parliament {parliament}")
    loader = f"posts-{parliament}"
    popolo = Popolo.from_path(path, cross_validate=False)
    store(
        build_organizations(popolo, parliament=parliament, loader=loader),
        offices=build_offices(
            popolo, parliament=parliament, loader=loader, quiet=quiet
        ),
        loaders=[loader],
        quiet=quiet,
    )


if __name__ == "__main__":
    app()
