from __future__ import annotations

import re
import unicodedata


def slugify(value: str) -> str:
    """
    Convert a name to an ASCII, lowercase, hyphen-separated slug.

    Matches the approach used by the parlparse committee scraper.
    """
    value = (
        unicodedata.normalize("NFKD", value).encode("ascii", "ignore").decode("ascii")
    )
    value = re.sub(r"[^\w\s-]", "", value).strip().lower()
    return re.sub(r"[-\s]+", "-", value)
