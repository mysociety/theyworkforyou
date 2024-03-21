#!/usr/bin/env python3
# encoding: utf-8
"""
division_io.py - Import and export division data from the TWFY database.

See python scripts/division_io.py --help for usage.

"""

import re
from enum import Enum
from pathlib import Path
from typing import cast
from warnings import filterwarnings

import MySQLdb
import pandas as pd
import rich_click as click
from rich import print
from rich.prompt import Prompt

# suppress warnings about using mysqldb in pandas
filterwarnings(
    "ignore",
    category=UserWarning,
    message=".*pandas only supports SQLAlchemy connectable.*",
)


class TitlePriority(str, Enum):
    ORIGINAL_HEADER = "ORIGINAL_HEADER"
    PARLIAMENT_DESCRIBED = "PARLIAMENT_DESCRIBED"
    MANUAL = "MANUAL"

    @classmethod
    def get_priority(cls, priority: str) -> int:

        lookup = {
            cls.ORIGINAL_HEADER: 1,
            cls.PARLIAMENT_DESCRIBED: 5,
            cls.MANUAL: 10,
        }
        return lookup[priority]


@click.group()
def cli():
    pass


def fast_config(config_path: Path) -> dict[str, str]:
    """
    There's a more comprehensive config parser in commonlib/pylib/mysociety/config.py
    But this is all we need for this function.
    """
    pattern = r"define\s*\(\s*['\"](.*?)['\"]\s*,\s*['\"]?(.*?)['\"]?\s*\);"

    with config_path.open("r") as f:
        content = f.read()

    return {key: value for key, value in re.findall(pattern, content)}


def get_twfy_db_connection() -> MySQLdb.Connection:
    config_path = Path("conf", "general")
    config = fast_config(config_path)

    db_connection = cast(
        MySQLdb.Connection,
        MySQLdb.connect(
            host=config["OPTION_TWFY_DB_HOST"],
            db=config["OPTION_TWFY_DB_NAME"],
            user=config["OPTION_TWFY_DB_USER"],
            passwd=config["OPTION_TWFY_DB_PASS"],
            charset="utf8",
        ),
    )
    return db_connection


def df_to_db(df: pd.DataFrame, *, new_priority: TitlePriority, verbose: bool = False):
    """
    Take a dataframe with:
    - a divison_id column or a twfy_votes_url column.
    - division_title column
    - optionally, yes_text and no_text.

    And load these descriptions into the divisions table.
    """

    if "twfy_votes_url" in df.columns:
        df["division_id"] = df["twfy_votes_url"].apply(twfy_votes_url_to_pw_id)

    if "division_id" not in df.columns:
        raise ValueError(
            "Dataframe to uplaod needs division_id or twfy_votes_url column"
        )

    if "division_title" not in df.columns:
        raise ValueError("Dataframe needs a division_title column")

    optional_col_count = 0

    if "yes_text" in df.columns:
        optional_col_count += 1

    if "no_text" in df.columns:
        optional_col_count += 1

    if optional_col_count == 1:
        raise ValueError("Dataframe either needs both yes_text and no_text or neither.")

    db_connection = get_twfy_db_connection()

    # get all divisions with a title_priority below or equal to current priority
    existing_df = pd.read_sql(
        f"SELECT division_id, title_priority FROM divisions",
        db_connection,
    )
    existing_df["int_title_priority"] = existing_df["title_priority"].apply(
        TitlePriority.get_priority
    )
    existing_df = existing_df[
        existing_df["int_title_priority"] <= TitlePriority.get_priority(new_priority)
    ]

    # limit dataframe to just those that can be updated

    count_before = len(df)
    df = df[df["division_id"].isin(existing_df["division_id"])]
    count_removed = count_before - len(df)
    if verbose:
        print(
            f"[blue]Updating titles for {len(df)} divisions - ignoring {count_removed} due to priority or absence."
        )
    if optional_col_count == 0:
        # update the division_title column in the database
        update_command = "UPDATE divisions SET division_title = %s, title_priority = %s WHERE division_id = %s"

        with db_connection.cursor() as cursor:
            update_data = [
                (row["division_title"], new_priority, row["division_id"])
                for _, row in df.iterrows()
            ]
            cursor.executemany(update_command, update_data)
        db_connection.commit()
        if verbose:
            print(f"[green]{len(df)} rows updated.")
    elif optional_col_count == 2:
        # update the division_title, yes_text, and no_text columns in the database
        update_command = "UPDATE divisions SET division_title = %s, title_priority = %s, yes_text = %s, no_text = %s WHERE division_id = %s"

        with db_connection.cursor() as cursor:
            update_data = [
                (
                    row["division_title"],
                    new_priority,
                    row["yes_text"],
                    row["no_text"],
                    row["division_id"],
                )
                for _, row in df.iterrows()
            ]
            cursor.executemany(update_command, update_data)
        db_connection.commit()
        if verbose:
            print(f"[green]{len(df)} rows updated.")

    db_connection.close()


def url_to_db(url: str, *, new_priority: TitlePriority):
    """
    Pipe external URL into the update process.
    """

    if url.endswith(".parquet"):
        df = pd.read_parquet(url)
    elif url.endswith(".csv"):
        df = pd.read_csv(url)
    elif url.endswith(".json"):
        df = pd.read_json(url)
    else:
        raise ValueError("File not an allowed type (csv, json, or parquet)")

    df_to_db(df, new_priority=new_priority)


def run_schema_update(verbose: bool = False):
    """
    Check if the title_priority priority column exists in the divisions table
    and if not, run db/0023-add-division-title-priority.sql
    """

    db_connection = get_twfy_db_connection()
    df = pd.read_sql("SELECT * FROM divisions limit 1", db_connection)
    if "title_priority" in df.columns:
        if verbose:
            print("[blue]Schema already updated[/blue]")
        return

    update_command = Path("db", "0023-add-division-title-priority.sql").read_text()

    # Execute the SQL update command
    with db_connection.cursor() as cursor:
        cursor.execute(update_command)
    db_connection.commit()
    if verbose:
        print("[green]Schema updated successfully[/green]")
    db_connection.close()


def twfy_votes_url_to_pw_id(url: str) -> str:
    """
    Take a twfy_votes style URL (/decisions/division/commons/2024-01-10/37)
    and put it in the pw id format
    """
    parts = url.split("/")
    division_no = parts[-1]
    date = parts[-2]
    chamber = parts[-3]
    return f"pw-{date}-{division_no}-{chamber}"


@cli.command()
@click.option("--verbose", is_flag=True, help="Show verbose output")
def export_division_data(verbose: bool = False):
    """
    Export division data to publically accessible parquet files
    """
    config = fast_config(Path("conf", "general"))
    raw_data_dir = Path(config["RAWDATA"])
    dest_path = raw_data_dir / "votes"
    dest_path.mkdir(parents=True, exist_ok=True)

    db_connection = get_twfy_db_connection()
    # get divisions
    df = pd.read_sql("SELECT * FROM divisions", db_connection)
    df.to_parquet(dest_path / "divisions.parquet", index=False)
    if verbose:
        print(f"[green]Divisions written to {dest_path / 'divisions.parquet'}[/green]")
    # get votes
    df = pd.read_sql("SELECT * FROM persondivisionvotes", db_connection)
    df.to_parquet(dest_path / "votes.parquet", index=False)
    if verbose:
        print(f"[green]Votes written to {dest_path / 'votes.parquet'}[/green]")

    db_connection.close()


@cli.command()
@click.option("--verbose", is_flag=True, help="Show verbose output")
def update_schema(verbose: bool = False):
    """
    Run schema update to support division titles if necessary
    """
    run_schema_update(verbose=verbose)


@cli.command()
def update_division_title():
    """
    Manually update a single division.
    """
    item = {
        "division_id": Prompt.ask("Division id (pw style)"),
        "division_title": Prompt.ask("Division title"),
        "yes_text": Prompt.ask(
            "Division yes text (optional)", default="", show_default=False
        ),
        "no_text": Prompt.ask(
            "Division no text (optional)", default="", show_default=False
        ),
    }

    if item["yes_text"] == "":
        del item["yes_text"]
    if item["no_text"] == "":
        del item["no_text"]
    df = pd.DataFrame([item])

    df_to_db(df, new_priority=TitlePriority.MANUAL)


@cli.command()
@click.option(
    "--url",
    prompt="Link to URL of manual update data",
    help="A csv, json or parquet file to update division information from.",
)
@click.option("--verbose", is_flag=True, help="Show verbose output")
def update_from_url(url: str, verbose: bool = False):
    """
    Fetch a bulk update file from a remote URL - assumes these are manual updates
    and so take priority over other approaches.
    """
    url_to_db(url, new_priority=TitlePriority.MANUAL, verbose=verbose)


@cli.command()
@click.option("--verbose", is_flag=True, help="Show verbose output")
def update_from_commons_votes(verbose: bool = False):
    """
    Import Commons votes description names into local database.
    Mid priority - higher than header based approaches, lower than manual approaches.
    """

    df = pd.read_parquet(
        "https://pages.mysociety.org/voting-data/data/commons_votes/latest/divisions.parquet"
    )

    df = df[["division_key", "title"]].rename(
        columns={"division_key": "division_id", "title": "division_title"}
    )

    df_to_db(df, new_priority=TitlePriority.PARLIAMENT_DESCRIBED, verbose=verbose)


def main():
    cli()


if __name__ == "__main__":
    main()
