#!/usr/bin/env python3
# encoding: utf-8
"""
import_search_suggestions.py - Import vector search suggestions

See python scripts/import_search_suggestions.py --help for usage.

"""

import re
import sys
from pathlib import Path
from typing import cast
from warnings import filterwarnings

import MySQLdb
import pandas as pd
import rich_click as click
from pylib.mysociety import config
from rich import print
from rich.prompt import Prompt

repository_path = Path(__file__).parent.parent

config.set_file(repository_path / "conf" / "general")

# suppress warnings about using mysqldb in pandas
filterwarnings(
    "ignore",
    category=UserWarning,
    message=".*pandas only supports SQLAlchemy connectable.*",
)


@click.group()
def cli():
    pass


def get_twfy_db_connection() -> MySQLdb.Connection:
    db_connection = cast(
        MySQLdb.Connection,
        MySQLdb.connect(
            host=config.get("TWFY_DB_HOST"),
            db=config.get("TWFY_DB_NAME"),
            user=config.get("TWFY_DB_USER"),
            passwd=config.get("TWFY_DB_PASS"),
            charset="utf8",
        ),
    )
    return db_connection


def df_to_db(df: pd.DataFrame, verbose: bool = False):
    """
    add search suggestions to the database
    """
    df = df.dropna(how="any")
    db_connection = get_twfy_db_connection()

    with db_connection.cursor() as cursor:
        # just remove everything and re-insert it all rather than trying to update things
        cursor.execute("DELETE FROM vector_search_suggestions")
        insert_command = "INSERT INTO vector_search_suggestions (search_term, search_suggestion) VALUES (%s, %s)"
        suggestion_data = [
            (row["original_query"], row["match"]) for _, row in df.iterrows()
        ]
        cursor.executemany(insert_command, suggestion_data)
    db_connection.commit()

    if verbose:
        print(f"[green]{len(df)} rows updated.")

    db_connection.close()


def url_to_db(url: str, verbose: bool = False):
    """
    Pipe external URL into the update process.
    """
    df = pd.read_csv(url)

    df_to_db(df, verbose=verbose)


def file_to_db(file: str, verbose: bool = False):
    """
    Pipe file into the update process.
    """
    df = pd.read_csv(file)

    df_to_db(df, verbose=verbose)


@cli.command()
@click.option(
    "--url",
    required=False,
    default=None,
    help="A csv file to update search suggestions from.",
)
@click.option(
    "--file",
    required=False,
    default=None,
    help="A csv file to update search suggestions from.",
)
@click.option("--verbose", is_flag=True, help="Show verbose output")
def update_vector_search_suggestions(url: str, file: str, verbose: bool = False):
    """
    Update the vector search suggestions
    """
    if file:
        file_to_db(file, verbose=verbose)
    elif url:
        url_to_db(url, verbose=verbose)


def main():
    cli()


if __name__ == "__main__":
    main()
