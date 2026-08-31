"""
Generic three-way sync between a set of freshly built model instances and the
rows already in a table.

Importers that own a subset of a table (identified by a `loader` column, or any
other filter) can hand over the rows they have just built and have new rows
created, changed rows updated, and rows that have gone away deleted - without
a delete-everything-then-reinsert window that would blank the table for anyone
reading it mid-import.

A row that already exists under the same key but belongs to somebody else - a
row an older loader wrote - is taken over rather than duplicated. Only rows the
importer owns are ever deleted.
"""

from __future__ import annotations

from typing import Any, Iterable, NamedTuple, Optional, Sequence, TypeVar

from django.db import models

ModelType = TypeVar("ModelType", bound=models.Model)


class SyncResult(NamedTuple):
    created: int
    updated: int
    unchanged: int
    deleted: int

    def __str__(self) -> str:
        return (
            f"[green]{self.created}[/green] created, "
            f"[blue]{self.updated}[/blue] updated, "
            f"{self.unchanged} unchanged, "
            f"[red]{self.deleted}[/red] deleted"
        )


def sync_table(
    model: type[ModelType],
    rows: Iterable[ModelType],
    *,
    key_field: str,
    update_fields: Sequence[str],
    owned: dict[str, Any],
    partition: Optional[dict[str, Any]] = None,
    batch_size: int = 1000,
) -> SyncResult:
    """
    Bring the rows of `model` into line with `rows`.

    `key_field` identifies a row, and must be the field django treats as the
    primary key so that bulk_update can find it again. Where the real key is
    composite - organization is keyed on org_id and language - the rest of the
    key goes in `partition`, the slice of the table within which `key_field` is
    unique.

    `owned` selects the rows this importer is responsible for. Rows inside the
    partition but outside `owned` can still be updated, if `rows` supplies the
    same key, but are never deleted.
    """
    queryset = model.objects.filter(**(partition or {}))
    existing: dict[Any, ModelType] = {getattr(row, key_field): row for row in queryset}
    owned_keys = set(queryset.filter(**owned).values_list(key_field, flat=True))

    to_create: list[ModelType] = []
    to_update: list[ModelType] = []
    unchanged = 0
    seen: set[Any] = set()

    for row in rows:
        key = getattr(row, key_field)
        seen.add(key)
        current = existing.get(key)
        if current is None:
            to_create.append(row)
            continue
        changes = [f for f in update_fields if getattr(current, f) != getattr(row, f)]
        if not changes:
            unchanged += 1
            continue
        for f in changes:
            setattr(current, f, getattr(row, f))
        to_update.append(current)

    if to_create:
        model.objects.bulk_create(to_create, batch_size=batch_size)
    if to_update:
        queryset.bulk_update(to_update, list(update_fields), batch_size=batch_size)

    deleted = 0
    stale = owned_keys - seen
    if stale:
        deleted, _ = queryset.filter(**{f"{key_field}__in": list(stale)}).delete()

    return SyncResult(len(to_create), len(to_update), unchanged, deleted)
