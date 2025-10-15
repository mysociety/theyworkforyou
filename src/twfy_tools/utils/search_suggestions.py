"""
Functions to update the vector search suggestions
"""

from pathlib import Path
from typing import Optional

import pandas as pd
import rich
import rich_click as click

from twfy_tools.common.config import config
from twfy_tools.db.models import VectorSearchSuggestions


@click.group()
def cli():
    pass


def df_to_db(df: pd.DataFrame, verbose: bool = False):
    """
    Add search suggestions to the database
    """
    df = df.dropna(how="any")

    to_create = []

    for _, row in df.iterrows():
        search_term = row["original_query"]
        search_suggestion = row["match"]
        to_create.append(
            VectorSearchSuggestions(
                search_term=search_term, search_suggestion=search_suggestion
            )
        )

    VectorSearchSuggestions.objects.all().delete()
    VectorSearchSuggestions.objects.bulk_create(to_create)

    if verbose:
        rich.print(f"[green]{len(df)}[/green] rows updated.")


@cli.command()
@click.option(
    "--file",
    required=False,
    default=None,
    help="A csv file/url to update search suggestions from.",
)
@click.option("--verbose", is_flag=True, help="Show verbose output")
def load(file: Optional[str], verbose: bool = False):
    """
    Update the vector search suggestions
    """

    if file is None:
        parlparse_dir = config.PWMEMBERS.parent
        search_dir = parlparse_dir / "rawdata" / "search"
        data_file = search_dir / "twfy_vector_search_suggestions.json"
    else:
        data_file = Path(file)

    if not data_file.exists():
        raise FileNotFoundError(f"File {data_file} does not exist")

    # open based on extention,  csv or json
    if data_file.suffix == ".json":
        df = pd.read_json(data_file)
    elif data_file.suffix == ".csv":
        df = pd.read_csv(data_file)
    else:
        raise ValueError(f"File {data_file} is not a csv or json file")

    df_to_db(df, verbose=verbose)


@cli.command()
def count():
    """
    For diagnostics to check import has worked
    """

    count = VectorSearchSuggestions.objects.count()

    rich.print(f"There are [green]{count}[/green] suggestions in the db")


def main():
    cli()


if __name__ == "__main__":
    main()
