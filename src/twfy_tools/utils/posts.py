"""
Import committee and post memberships from the parlparse members/posts popolo
files into the organization and moffice tables.

There is one file per parliament. Each holds the committees of that parliament
as organizations, and memberships that are either a seat on one of those
committees or a role held directly in the chamber (a minister, a whip, an
opposition spokesperson).
"""

from __future__ import annotations

import datetime
import re
import unicodedata
from pathlib import Path
from typing import Any, Optional

import rich
from mysoc_validator import Popolo
from mysoc_validator.models.popolo import Membership
from mysoc_validator.models.popolo import Organization as PopoloOrganization
from typer import Typer

from twfy_tools.common.config import config
from twfy_tools.db.models import Moffice, Organization
from twfy_tools.db.table_sync import sync_table

app = Typer(pretty_exceptions_enable=False)

PARLIAMENT_FILES = {
    "uk": "westminster-parliament-posts.json",
    "scotland": "scottish-parliament-committees.json",
    "wales": "senedd-committees.json",
    "northern-ireland": "ni-assembly-committees.json",
}

# The Senedd files carry a Welsh version of every name, description and role.
LANGUAGES = ["en", "cy"]

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


def slugify(value: str) -> str:
    """
    Convert a name to an ascii, lowercase, hyphen separated slug.
    Same approach as the parlparse committee scraper.
    """
    value = (
        unicodedata.normalize("NFKD", str(value))
        .encode("ascii", "ignore")
        .decode("ascii")
    )
    value = re.sub(r"[^\w\s-]", "", value).strip().lower()
    return re.sub(r"[-\s]+", "-", value)


def localised_value(item: Any, field: str, language: str) -> Optional[str]:
    """
    Fetch an explicitly localised value, or None if the item doesn't have one.

    Unlike the validator's get_localised_value this does not fall back to the
    canonical field, because for the Senedd that holds the combined
    "Y Pwyllgor Cyllid / Finance Committee" form, which is not what we want to
    store in a single-language row.
    """
    extra = getattr(item, "extra", None)
    if extra is None:
        return None
    for label in extra.localised_values:
        if label.field == field and label.language == language:
            return label.value
    return None


def has_language(item: Any, language: str) -> bool:
    extra = getattr(item, "extra", None)
    if extra is None:
        return False
    return any(label.language == language for label in extra.localised_values)


def organization_tags(org: PopoloOrganization, parliament: str) -> str:
    """
    Committee categories, as a comma separated list.

    Senedd tags are skipped: they hold the bilingual pair of the committee's
    own name rather than a category, so they would be noise.
    """
    if parliament == "wales":
        return ""
    extra = getattr(org, "extra", None)
    tags = getattr(extra, "tags", None) if extra else None
    return ",".join(tags) if tags else ""


def organization_url(org: PopoloOrganization, language: str) -> str:
    """
    Pick the link matching the language. Senedd committees have a senedd.cymru
    and a senedd.wales link; everyone else has one link or none.
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
        languages = ["en"] + [
            language
            for language in LANGUAGES
            if language != "en" and has_language(org, language)
        ]
        for language in languages:
            name = localised_value(org, "name", language) or org.name
            description = (
                localised_value(org, "description", language) or org.description or ""
            )
            slug = unique_slug(
                slugify(name), used_slugs.setdefault((language, parliament), set())
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
                    url=organization_url(org, language),
                    tags=organization_tags(org, parliament),
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


def as_date(value: Any, default: datetime.date) -> datetime.date:
    """
    Popolo uses year 1 and year 9999 sentinels for unknown dates, and may hand
    back an approximate date for a partial one.
    """
    for attribute in ("earliest_date", "latest_date"):
        if hasattr(value, attribute) and not isinstance(value, datetime.date):
            value = getattr(value, attribute)
            break
    if not isinstance(value, datetime.date):
        return default
    if value < MIN_DATE:
        return default
    return datetime.date(value.year, value.month, value.day)


def build_office(
    membership: Membership,
    org: Optional[PopoloOrganization],
    parliament: str,
    loader: str,
) -> Moffice:
    """
    A membership either sits on a committee in this file, or is a role held in
    the chamber itself. Chamber roles get an empty dept so that
    prettify_office() shows the role on its own, as the old Perl loader did for
    the House of Commons.
    """
    if org is not None:
        english_name = localised_value(org, "name", "en") or org.name
        position = localised_value(membership, "role", "en") or membership.role
        return Moffice(
            moffice_id=membership.id,
            dept=english_name,
            position=position or "Member",
            position_cy=localised_value(membership, "role", "cy") or "",
            from_date=as_date(membership.start_date, MIN_DATE),
            to_date=as_date(membership.end_date, MAX_DATE),
            person=int(membership.person_id.split("/")[-1]),
            source=membership.source or "",
            org_id=org.id,
            post_type="committee",
            parliament=parliament,
            loader=loader,
        )

    position = localised_value(membership, "role", "en") or membership.role
    return Moffice(
        moffice_id=membership.id,
        dept="",
        position=position or "Member",
        position_cy=localised_value(membership, "role", "cy") or "",
        from_date=as_date(membership.start_date, MIN_DATE),
        to_date=as_date(membership.end_date, MAX_DATE),
        person=int(membership.person_id.split("/")[-1]),
        source=membership.source or "",
        org_id=None,
        post_type=SOURCE_POST_TYPES.get(membership.source or "", "government"),
        parliament=parliament,
        loader=loader,
    )


def build_offices(
    popolo: Popolo, parliament: str, loader: str, quiet: bool = False
) -> list[Moffice]:
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
        rows.append(build_office(membership, org, parliament, loader))

    if skipped and not quiet:
        rich.print(
            f"[yellow]Skipped {skipped} {parliament} memberships held through a post[/yellow]"
        )
    return rows


def read_parliament(
    parliament: str, quiet: bool = False
) -> Optional[tuple[list[Organization], list[Moffice]]]:
    """
    Read one parliament's file, or return None if it isn't in the checkout yet.
    """
    path = config.PWMEMBERS / "posts" / PARLIAMENT_FILES[parliament]
    if not path.exists():
        rich.print(f"[yellow]No file for {parliament} at {path}, skipping[/yellow]")
        return None

    loader = f"posts-{parliament}"
    popolo = Popolo.from_path(path, cross_validate=False)
    organizations = build_organizations(popolo, parliament, loader)
    offices = build_offices(popolo, parliament, loader, quiet=quiet)
    if not quiet:
        rich.print(
            f"Read [blue]{len(organizations)}[/blue] organizations and "
            f"[blue]{len(offices)}[/blue] memberships for {parliament}"
        )
    return organizations, offices


def store(
    organizations: list[Organization],
    offices: list[Moffice],
    loaders: list[str],
    quiet: bool = False,
):
    """
    Sync both tables. Rows an older loader wrote under the same id are taken
    over; only rows owned by these loaders are deleted.
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
def load_all(quiet: bool = False):
    """
    Load committee and post memberships for every parliament.
    """
    organizations: list[Organization] = []
    offices: list[Moffice] = []

    for parliament in PARLIAMENT_FILES:
        read = read_parliament(parliament, quiet=quiet)
        if read is None:
            continue
        organizations.extend(read[0])
        offices.extend(read[1])

    if not offices:
        rich.print("[red]No posts files found, nothing to load[/red]")
        return

    store(
        organizations,
        offices,
        [f"posts-{parliament}" for parliament in PARLIAMENT_FILES],
        quiet=quiet,
    )


@app.command()
def load_parliament(parliament: str, quiet: bool = False):
    """
    Load a single parliament, for debugging.
    """
    if parliament not in PARLIAMENT_FILES:
        raise ValueError(
            f"Unknown parliament {parliament}, expected one of "
            f"{', '.join(PARLIAMENT_FILES)}"
        )
    read = read_parliament(parliament, quiet=quiet)
    if read is None:
        return
    store(read[0], read[1], [f"posts-{parliament}"], quiet=quiet)


@app.command()
def load_file(path: Path, parliament: str, quiet: bool = False):
    """
    Load an arbitrary posts file as the given parliament, for debugging.
    """
    loader = f"posts-{parliament}"
    popolo = Popolo.from_path(path, cross_validate=False)
    store(
        build_organizations(popolo, parliament, loader),
        build_offices(popolo, parliament, loader, quiet=quiet),
        [loader],
        quiet=quiet,
    )


if __name__ == "__main__":
    app()
