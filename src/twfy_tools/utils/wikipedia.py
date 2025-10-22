"""
Interface to load wikipedia allowlist and blocklist.
"""

import json
from pathlib import Path

from django.db.models import Model

import typer
from pydantic import TypeAdapter
from rich import print
from typer import Typer

from twfy_tools.common.config import config
from twfy_tools.db.models import Titles, TitlesIgnored

app = Typer(invoke_without_command=True)

wikipedia_data = config.RAWDATA / "wikilinks" / "lists"


def sync_folder(model: type[Model], path: Path, quiet: bool = False):
    with path.open() as f:
        title_list = TypeAdapter(list[str]).validate_python(json.load(f))

    # The unique key is the lowercased stripped title, so we need to deduplicate
    # the list before inserting.
    keys = []
    cleaned_titles = []
    for title in title_list:
        key = title.strip().lower()
        if key and key not in keys:
            keys.append(key)
            cleaned_titles.append(title)

    delete_count = model.objects.all().delete()

    if not quiet:
        print(
            f"Deleted [red]{delete_count[0]}[/red] existing titles from {model.__name__}"
        )

    if not quiet:
        print(
            f"Loading [blue]{len(cleaned_titles)}[/blue] titles into {model.__name__}"
        )

    titles = [model(title=title) for title in cleaned_titles]
    model.objects.bulk_create(titles, batch_size=1000)


@app.command()
def load_allowlist(quiet: bool = False):
    """
    Load the Wikipedia allowlist into the database.
    """
    allow_list_path = wikipedia_data / "allowlist.json"
    sync_folder(Titles, allow_list_path, quiet=quiet)


@app.command()
def load_blocklist(quiet: bool = False):
    """
    Load the Wikipedia blocklist into the database.
    """
    block_list_path = wikipedia_data / "blocklist.json"
    sync_folder(TitlesIgnored, block_list_path, quiet=quiet)


@app.callback()
def main(
    ctx: typer.Context,
    quiet: bool = typer.Option(False, "--quiet", "-q", help="Suppress output messages"),
):
    """
    Default to load allowlist and blocklist if no subcommand is given.
    """
    if ctx.invoked_subcommand is None:
        # No subcommand was invoked, run load_all as default
        load_allowlist(quiet)


if __name__ == "__main__":
    app()
