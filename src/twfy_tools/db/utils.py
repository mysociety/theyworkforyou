import json
from itertools import groupby
from typing import Any, Mapping, Union

from django.utils import timezone

import rich
from mysoc_validator.models.interests import RegmemPerson
from pydantic import BaseModel

from .models import PersonInfo

# specify the validation model to be applied to a common key prefix
validation_lookup: dict[str, type[BaseModel]] = {
    "person_regmem_": RegmemPerson,
}


def upload_person_info_values(
    values: list[PersonInfo],
    *,
    remove_absent: bool = False,
    quiet: bool = False,
    batch_size: int = 500,
):
    """
    Upload a list of PersonInfo values to the database.
    Checks which values are already present to do efficient bulk updates and creates.
    The remove_absent bool will remove any entries for person-id/key pairs not present in this upload.
    e.g. if you always reupload the same file, and you've remove an entry from the file.
    This being true will remove the entry from the database.
    """

    values = sorted(values, key=lambda x: x.data_key)

    now = timezone.now()

    for key, group in groupby(values, key=lambda x: x.data_key):
        # get all person_ids for this key
        group = list(group)
        for g in group:
            g.data_value = g.str_data_value()
        rich.print(f"Uploading [blue]{len(group)}[/blue] values for {key}")
        value_lookup = {x.person_id: x for x in group}
        person_ids = [x.person_id for x in group]

        existing_entries = list(
            PersonInfo.objects.filter(data_key=key, person_id__in=person_ids)
        )
        existing_person_ids = [x.person_id for x in existing_entries]
        to_update = []
        same_value_count = 0
        for e in existing_entries:
            new_value = value_lookup[e.person_id].data_value
            old_value = e.data_value
            if new_value != old_value:
                e.data_value = new_value
                e.lastupdate = now
                to_update.append(e)
            else:
                same_value_count += 1

        new_entries = [x for x in group if x.person_id not in existing_person_ids]

        for n in new_entries:
            n.lastupdate = now

        if not quiet:
            rich.print(
                f"Found [blue]{len(existing_person_ids)}[/blue] existing entries for people"
            )

        # update existing entries
        if not quiet:
            rich.print(f"Updating [blue]{len(to_update)}[/blue] entries")
        PersonInfo.objects.filter(data_key=key).bulk_update(
            to_update, ["data_value", "lastupdate"], batch_size=batch_size
        )

        # create new entries
        if not quiet:
            rich.print(f"Creating [green]{len(new_entries)}[/green] entries")
        PersonInfo.objects.bulk_create(new_entries, batch_size=batch_size)

        if remove_absent:
            deleted_count, _ = (
                PersonInfo.objects.filter(data_key=key)
                .exclude(person_id__in=person_ids)
                .delete()
            )
            if deleted_count and not quiet:
                rich.print(f"Deleted [red]{deleted_count}[/red] entries")


def upload_person_info(
    key_name: str,
    person_id_to_value: Mapping[int, Union[str, dict[Any, Any], list[Any], BaseModel]],
    *,
    validate: bool = True,
    remove_absent: bool = False,
    quiet: bool = False,
    batch_size: int = 500,
):
    """
    Prepare a set of data (via dicts or pydantic models) to be uploaded to personinfo table.
    """
    validation_model = None

    if validate:
        for key_prefix, value in validation_lookup.items():
            if key_name.startswith(key_prefix):
                validation_model = value
                break

        if not quiet:
            if validation_model:
                rich.print(f"Validating with {validation_model.__name__}")
            else:
                rich.print(f"No validation model specified for {key_name}")

    items = []
    now = timezone.now()
    for person_id, value in person_id_to_value.items():
        if validation_model:
            # enforce validation check if validated
            if isinstance(value, BaseModel):
                if not isinstance(value, validation_model):
                    raise ValueError(
                        f"Value for {key_name} is not a {validation_model.__name__}"
                    )
            elif isinstance(value, dict):
                validation_model.model_validate(value)
            else:
                # if validation is on, don't want to process non-dicts
                raise ValueError(f"Value for {key_name} is not a dict or a valid model")

        if isinstance(value, BaseModel):
            str_value = value.model_dump_json()
        elif isinstance(value, (dict, list)):
            str_value = json.dumps(value)
        elif isinstance(value, (int, float)):
            str_value = str(value)
        elif isinstance(value, str):
            str_value = value
        else:
            raise ValueError(f"type: {type(value)} not supported")

        items.append(
            PersonInfo(
                person_id=person_id,
                data_key=key_name,
                data_value=str_value,
                lastupdate=now,
            )
        )

    if not quiet:
        rich.print(f"Uploading [blue]{len(items)}[/blue] items for {key_name}")

    upload_person_info_values(
        items, remove_absent=remove_absent, quiet=quiet, batch_size=batch_size
    )
