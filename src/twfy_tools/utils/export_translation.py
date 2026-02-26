"""
Export and import translation data from Welsh .po files.
Handles missing and fuzzy translations, exports to JSON/CSV, and can update .po files.
"""

from pathlib import Path
from typing import Optional

import pandas as pd
import polib
import typer
from pydantic_store import BaseModel, ListModel
from rich import print
from rich.table import Table
from typer import Typer

from ..common.enum_backport import StrEnum

app = Typer()


class TranslationType(StrEnum):
    MISSING = "missing"
    FUZZY = "fuzzy"
    AUTO = "auto"


class OutputFormat(StrEnum):
    JSON = "json"
    CSV = "csv"


class TranslationEntry(BaseModel):
    """
    A single translation entry with type, original text, current translation, and location info.
    """

    translation_type: TranslationType
    original: str
    current_translation: str
    locations: list[str]  # List of "filename:line" strings


class TranslationList(ListModel[TranslationEntry]):
    """
    Root model for a list of translation entries.
    """

    pass


def get_welsh_po_path() -> Path:
    """
    Get the path to the Welsh .po file.
    """
    return Path("/twfy/locale/cy_GB.UTF-8/LC_MESSAGES/TheyWorkForYou.po")


def extract_problematic_translations(po_file: polib.POFile) -> list[TranslationEntry]:
    """
    Extract missing, fuzzy, and auto-translated translations from a .po file.
    """
    entries = []

    for entry in po_file:
        # Skip metadata entry
        if not entry.msgid:
            continue

        # Check for auto-translated entries (check comments for auto-translation indicators)
        is_auto_translated = False
        # Check both comment and tcomment (translator comment) fields
        comments_to_check = []
        if entry.comment:
            comments_to_check.append(entry.comment)
        if hasattr(entry, "tcomment") and entry.tcomment:
            comments_to_check.append(entry.tcomment)

        if comments_to_check:
            # Look for various auto-translation indicators in comments
            auto_indicators = [
                "auto-translated",
                "auto translated",
                "google translated",
                "machine translated",
                "automatically translated",
            ]
            for comment in comments_to_check:
                comment_lower = comment.lower()
                if any(indicator in comment_lower for indicator in auto_indicators):
                    is_auto_translated = True
                    break

        # Extract location information
        locations = []
        for occurrence in entry.occurrences:
            filename, line_number = occurrence
            if line_number:
                locations.append(f"{filename}:{line_number}")
            else:
                locations.append(filename)

        if is_auto_translated and entry.msgstr:
            entries.append(
                TranslationEntry(
                    translation_type=TranslationType.AUTO,
                    original=entry.msgid,
                    current_translation=entry.msgstr,
                    locations=locations,
                )
            )
        # Check for missing translations
        elif not entry.msgstr:
            entries.append(
                TranslationEntry(
                    translation_type=TranslationType.MISSING,
                    original=entry.msgid,
                    current_translation="",
                    locations=locations,
                )
            )
        # Check for fuzzy translations
        elif "fuzzy" in entry.flags:
            entries.append(
                TranslationEntry(
                    translation_type=TranslationType.FUZZY,
                    original=entry.msgid,
                    current_translation=entry.msgstr,
                    locations=locations,
                )
            )

    return entries


def save_to_json(entries: list[TranslationEntry], output_file: Path) -> None:
    """
    Save translation entries to JSON file.
    """
    translation_list = TranslationList(entries)
    translation_list.to_file(output_file)
    print(f"✅ Exported {len(entries)} entries to {output_file}")


def save_to_csv(entries: list[TranslationEntry], output_file: Path) -> None:
    """
    Save translation entries to CSV file using pandas.
    """
    # Use pydantic-store to save as JSON first, then convert to CSV via pandas
    translation_list = TranslationList(entries)
    json_data = translation_list.model_dump()
    df = pd.DataFrame(json_data)
    df.to_csv(output_file, index=False, encoding="utf-8")
    print(f"✅ Exported {len(entries)} entries to {output_file}")


def load_translations_from_json(input_file: Path) -> dict[str, str]:
    """
    Load translations from JSON file.
    """
    translation_list = TranslationList.from_file(input_file)
    translations = {}
    for entry in translation_list:
        if entry.current_translation:  # Only import non-empty translations
            translations[entry.original] = entry.current_translation
    return translations


def load_translations_from_csv(input_file: Path) -> dict[str, str]:
    """
    Load translations from CSV file using pandas.
    """
    df = pd.read_csv(input_file, encoding="utf-8")
    # Convert to dict format expected by validation
    data = df.to_dict("records")

    translation_list = TranslationList.model_validate(data)
    translations = {}
    for entry in translation_list:
        if entry.current_translation:  # Only import non-empty translations
            translations[entry.original] = entry.current_translation
    return translations


@app.command()
def export(
    output_file: Optional[Path] = typer.Argument(
        None,
        help="Output file path. If not provided, defaults to translations.json or translations.csv",
    ),
    format: OutputFormat = typer.Option(
        OutputFormat.JSON, "--format", "-f", help="Output format"
    ),
    po_file: Optional[Path] = typer.Option(
        None,
        "--po-file",
        "-p",
        help="Path to .po file (defaults to Welsh translation file)",
    ),
    include_missing: bool = typer.Option(
        True, "--include-missing/--no-missing", help="Include missing translations"
    ),
    include_fuzzy: bool = typer.Option(
        True, "--include-fuzzy/--no-fuzzy", help="Include fuzzy translations"
    ),
    include_auto: bool = typer.Option(
        True, "--include-auto/--no-auto", help="Include auto-translated entries"
    ),
):
    """
    Export missing, fuzzy, and/or auto-translated translations from Welsh .po file.
    """

    # Determine input file
    if po_file is None:
        po_file = get_welsh_po_path()

    if not po_file.exists():
        print(f"❌ .po file not found: {po_file}")
        raise typer.Exit(1)

    # Determine output file
    if output_file is None:
        extension = "json" if format == OutputFormat.JSON else "csv"
        output_file = Path(f"translations.{extension}")

    # Load and process .po file
    try:
        po = polib.pofile(str(po_file))
    except Exception as e:
        print(f"❌ Error loading .po file: {e}")
        raise typer.Exit(1)

    # Extract problematic translations
    all_entries = extract_problematic_translations(po)

    # Filter based on user preferences
    filtered_entries = []
    for entry in all_entries:
        if entry.translation_type == TranslationType.MISSING and include_missing:
            filtered_entries.append(entry)
        elif entry.translation_type == TranslationType.FUZZY and include_fuzzy:
            filtered_entries.append(entry)
        elif entry.translation_type == TranslationType.AUTO and include_auto:
            filtered_entries.append(entry)

    if not filtered_entries:
        print("✅ No problematic translations found!")
        return

    # Export to specified format
    if format == OutputFormat.JSON:
        save_to_json(filtered_entries, output_file)
    else:
        save_to_csv(filtered_entries, output_file)

    # Show summary
    missing_count = sum(
        1 for e in filtered_entries if e.translation_type == TranslationType.MISSING
    )
    fuzzy_count = sum(
        1 for e in filtered_entries if e.translation_type == TranslationType.FUZZY
    )
    auto_count = sum(
        1 for e in filtered_entries if e.translation_type == TranslationType.AUTO
    )

    table = Table(title="Export Summary")
    table.add_column("Type", style="cyan")
    table.add_column("Count", style="magenta")

    if include_missing:
        table.add_row("Missing", str(missing_count))
    if include_fuzzy:
        table.add_row("Fuzzy", str(fuzzy_count))
    if include_auto:
        table.add_row("Auto-translated", str(auto_count))
    table.add_row("Total", str(len(filtered_entries)))

    print(table)


@app.command()
def import_translations(
    input_file: Path = typer.Argument(
        ..., help="Input file with translations (JSON or CSV)"
    ),
    po_file: Optional[Path] = typer.Option(
        None,
        "--po-file",
        "-p",
        help="Path to .po file to update (defaults to Welsh translation file)",
    ),
    dry_run: bool = typer.Option(
        False,
        "--dry-run",
        "-n",
        help="Show what would be changed without making changes",
    ),
    remove_fuzzy: bool = typer.Option(
        True,
        "--remove-fuzzy/--keep-fuzzy",
        help="Remove fuzzy flag when updating translations",
    ),
):
    """
    Import translations from JSON or CSV file and update .po file.
    """

    # Determine target .po file
    if po_file is None:
        po_file = get_welsh_po_path()

    if not po_file.exists():
        print(f"❌ .po file not found: {po_file}")
        raise typer.Exit(1)

    if not input_file.exists():
        print(f"❌ Input file not found: {input_file}")
        raise typer.Exit(1)

    # Load translations from input file
    try:
        if input_file.suffix.lower() == ".json":
            translations = load_translations_from_json(input_file)
        elif input_file.suffix.lower() == ".csv":
            translations = load_translations_from_csv(input_file)
        else:
            print("❌ Unsupported file format. Use .json or .csv")
            raise typer.Exit(1)
    except Exception as e:
        print(f"❌ Error loading input file: {e}")
        raise typer.Exit(1)

    if not translations:
        print("❌ No translations found in input file")
        return

    # Load .po file
    try:
        po = polib.pofile(str(po_file))
    except Exception as e:
        print(f"❌ Error loading .po file: {e}")
        raise typer.Exit(1)

    # Update translations
    updated_count = 0
    updates = []

    for entry in po:
        if entry.msgid in translations:
            old_translation = entry.msgstr
            new_translation = translations[entry.msgid]

            if old_translation != new_translation:
                # Check if this was auto-translated
                is_auto_translated = False
                # Check both comment and tcomment (translator comment) fields
                comments_to_check = []
                if entry.comment:
                    comments_to_check.append(entry.comment)
                if hasattr(entry, "tcomment") and entry.tcomment:
                    comments_to_check.append(entry.tcomment)

                if comments_to_check:
                    auto_indicators = [
                        "auto-translated",
                        "auto translated",
                        "google translated",
                        "machine translated",
                        "automatically translated",
                    ]
                    for comment in comments_to_check:
                        comment_lower = comment.lower()
                        if any(
                            indicator in comment_lower for indicator in auto_indicators
                        ):
                            is_auto_translated = True
                            break

                updates.append(
                    {
                        "msgid": entry.msgid,
                        "old": old_translation,
                        "new": new_translation,
                        "was_fuzzy": "fuzzy" in entry.flags,
                        "was_auto": is_auto_translated,
                    }
                )

                if not dry_run:
                    entry.msgstr = new_translation
                    if remove_fuzzy and "fuzzy" in entry.flags:
                        entry.flags.remove("fuzzy")
                    updated_count += 1

    # Show results
    if dry_run:
        if updates:
            print(f"🔍 Would update {len(updates)} translations:")
            for update in updates[:10]:  # Show first 10
                status_parts = []
                if update["was_fuzzy"]:
                    status_parts.append("fuzzy")
                if update.get("was_auto", False):
                    status_parts.append("auto")
                status = f" ({', '.join(status_parts)})" if status_parts else ""
                print(f"  • {update['msgid'][:50]}...{status}")
                print(f"    Old: '{update['old'][:50]}...'")
                print(f"    New: '{update['new'][:50]}...'")
            if len(updates) > 10:
                print(f"  ... and {len(updates) - 10} more")
        else:
            print("✅ No changes needed")
    else:
        if updated_count > 0:
            po.save(str(po_file))
            print(f"✅ Updated {updated_count} translations in {po_file}")

            # Show summary
            fuzzy_updated = sum(1 for u in updates if u["was_fuzzy"])
            auto_updated = sum(1 for u in updates if u.get("was_auto", False))
            missing_updated = updated_count - fuzzy_updated - auto_updated

            table = Table(title="Import Summary")
            table.add_column("Type", style="cyan")
            table.add_column("Count", style="magenta")

            if missing_updated > 0:
                table.add_row("Missing → Translated", str(missing_updated))
            if fuzzy_updated > 0:
                table.add_row("Fuzzy → Updated", str(fuzzy_updated))
            if auto_updated > 0:
                table.add_row("Auto → Updated", str(auto_updated))
            table.add_row("Total Updated", str(updated_count))

            print(table)
        else:
            print("✅ No changes needed")


@app.command()
def status(
    po_file: Optional[Path] = typer.Option(
        None,
        "--po-file",
        "-p",
        help="Path to .po file (defaults to Welsh translation file)",
    ),
):
    """
    Show status of translations in .po file.
    """

    # Determine input file
    if po_file is None:
        po_file = get_welsh_po_path()

    if not po_file.exists():
        print(f"❌ .po file not found: {po_file}")
        raise typer.Exit(1)

    # Load and analyze .po file
    try:
        po = polib.pofile(str(po_file))
    except Exception as e:
        print(f"❌ Error loading .po file: {e}")
        raise typer.Exit(1)

    total = 0
    missing = 0
    fuzzy = 0
    auto = 0
    translated = 0

    for entry in po:
        # Skip metadata
        if not entry.msgid:
            continue

        total += 1

        # Check for auto-translated entries first
        is_auto_translated = False
        # Check both comment and tcomment (translator comment) fields
        comments_to_check = []
        if entry.comment:
            comments_to_check.append(entry.comment)
        if hasattr(entry, "tcomment") and entry.tcomment:
            comments_to_check.append(entry.tcomment)

        if comments_to_check:
            auto_indicators = [
                "auto-translated",
                "auto translated",
                "google translated",
                "machine translated",
                "automatically translated",
            ]
            for comment in comments_to_check:
                comment_lower = comment.lower()
                if any(indicator in comment_lower for indicator in auto_indicators):
                    is_auto_translated = True
                    break

        if is_auto_translated and entry.msgstr:
            auto += 1
        elif not entry.msgstr:
            missing += 1
        elif "fuzzy" in entry.flags:
            fuzzy += 1
        else:
            translated += 1

    # Calculate percentages
    if total > 0:
        translated_pct = (translated / total) * 100
        missing_pct = (missing / total) * 100
        fuzzy_pct = (fuzzy / total) * 100
        auto_pct = (auto / total) * 100
    else:
        translated_pct = missing_pct = fuzzy_pct = auto_pct = 0

    # Display results
    table = Table(title=f"Translation Status: {po_file.name}")
    table.add_column("Status", style="cyan")
    table.add_column("Count", style="magenta")
    table.add_column("Percentage", style="green")

    table.add_row("✅ Translated", str(translated), f"{translated_pct:.1f}%")
    table.add_row("🤖 Auto-translated", str(auto), f"{auto_pct:.1f}%")
    table.add_row("🔄 Fuzzy", str(fuzzy), f"{fuzzy_pct:.1f}%")
    table.add_row("❌ Missing", str(missing), f"{missing_pct:.1f}%")
    table.add_row("📊 Total", str(total), "100.0%")

    print(table)


if __name__ == "__main__":
    app()
