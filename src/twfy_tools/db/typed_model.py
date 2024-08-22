"""
Quick helper for lightweight typed models in Django.

Django models don't work well with typehints. This uses a quick
wrapper to give dataclass style prompts on incorrect types or missing fields
on class construction.

For a basic conversion, you just need to change to use the
field wrappper. This lets django fields be used while not conflicting
with the dataclass typehint.

so rather than

class ExampleModel(models.Model):
    field1 = models.CharField(max_length=255)
    field2 = models.IntegerField()

You would write:

class ExampleModel(TypedModel):
    field1: str = field(CharField, max_length=255)
    field2: int = field(IntegerField)

As with pydantic, the field defintions can be moved to annotations.
Here we just use the regular django fields as the annotations.
We can also use pydantic validations in the annotations.


e.g.

CharField = Annotated[
    str, models.CharField(max_length=255), StringConstraints(max_length=255)
    ]
IntegerField = Annotated[int, models.IntegerField()]

class ExampleModel(TypedModel):
    field1: CharField
    field2: IntegerField

As in dataclasses, default can be specified either in the field definition.

class ExampleModel(TypedModel):
    field1: CharField = field(CharField, max_length=255, default="default")
    field2: IntegerField

Or just by specifying the default in the class definition

class ExampleModel(TypedModel):
    field1: CharField = "default"
    field2: IntegerField = 15


Pydantic validation is only run on model creation when not made by the database.
This is to avoid validation errors when loading from the database.

When modifying properties of the model, the pydantic model is kept in sync and will
validate the changes.

e.g.

e.g.

ExampleModel(field1="test") # will validate
ExampleModel(field1="test", field2=15) # will validate
ExampleModel(field1="test", field2="15") # will raise a validation error

model = ExampleModel(field1="test")
model.field2 = 15 # will validate
model.field2 = "15" # will raise a validation error

"""

from datetime import datetime
from typing import (
    Annotated,
    Any,
    Callable,
    ClassVar,
    NamedTuple,
    Optional,
    Type,
    TypeVar,
    Union,
    get_args,
    get_origin,
)

from django.db import models
from django.forms.models import model_to_dict

from pydantic import BaseModel, ConfigDict, StringConstraints, create_model
from pydantic.fields import Field as PydanticField
from typing_extensions import ParamSpec, dataclass_transform

FieldType = TypeVar(
    "FieldType",
    bound=models.Field,
)
P = ParamSpec("P")

# Demonstration of storing model fields in Annotations, plus mixing with
# pydantic validations
PrimaryKey = Annotated[
    Optional[int], models.AutoField(primary_key=True), PydanticField(default=None)
]
CharField = Annotated[
    str, models.CharField(max_length=255), StringConstraints(max_length=255)
]
TextField = Annotated[str, models.TextField()]
IntegerField = Annotated[int, models.IntegerField()]
PositiveIntegerField = Annotated[
    int, models.PositiveIntegerField(), PydanticField(gt=0)
]
DateTimeField = Annotated[datetime, models.DateTimeField()]


class ExtraKwargs(NamedTuple):
    kwargs: dict[str, Any]


def blank_callable(*args: Any, **kwargs: Any) -> Any:
    pass


def field(
    model_class: Callable[P, FieldType] = blank_callable,
    null: bool = False,
    *args: P.args,
    **kwargs: P.kwargs,
) -> Any:
    """
    Helper function to hide Field creation - but return Any
    So the type checker doesn't complain about the return type
    and you can specify the specify type of the item as a typehint.
    """
    if args:
        raise ValueError("Positional arguments are not supported")
    kwargs["null"] = null
    if model_class == blank_callable:
        return ExtraKwargs(kwargs)
    elif isinstance(model_class, type) and issubclass(model_class, models.Field):
        return model_class(**kwargs)
    else:
        raise ValueError(f"Invalid model class {model_class}")


def pure_pydantic_annotations(type: Any) -> Any:
    """
    Pydantic constructor doesn't like having the django
    fields in there. This function removes them.
    """

    # If not annotated - back we go
    if get_origin(type) not in [Annotated, Union, Optional]:
        return Annotated[type, PydanticField()]

    # If Annotated - we need to look at the metadata
    # and remove anything that is an instance of models.Field

    metadata = list(get_args(type))
    base_type = metadata.pop(0)
    new_metadata = [m for m in metadata if not isinstance(m, models.Field)]

    # If there is *nothing* left, return the the basic type
    if len(new_metadata) == 0:
        if get_origin(type) not in [Annotated, Union, Optional]:
            return Annotated[type, PydanticField()]
        else:
            return base_type

    # If there is anything else left, return that as a new Annotated
    return Annotated[tuple([base_type] + new_metadata)]


def copy_field(field: models.Field) -> models.Field:
    """
    When moving things from annotations to construct django fields
    Need to copy the field so different field objects are assigned.
    """
    name, import_path, args, kwargs = field.deconstruct()
    return field.__class__(*args, **kwargs)


FieldType = TypeVar("FieldType", bound=models.Field)


def merge_field_instances(fields: list[FieldType]) -> FieldType:
    kwargs = {}
    for field in fields:
        name, import_path, args, field_kwargs = field.deconstruct()
        kwargs.update(field_kwargs)
    return fields[0].__class__(**kwargs)


@dataclass_transform(kw_only_default=True, field_specifiers=(field,))
class TypedModelBase(models.base.ModelBase):
    def __new__(cls, name: str, bases: tuple[type], dct: dict[str, Any], **kwargs: Any):
        """
        Thie goal of this wrapper is to construct a
        normal django model while giving the relevant typehints.

        This does several things:
        - Extracts the fields from the annotations
        - Or where a field has been directly specified
        - Merges in any defaults specified
        - Creates a parallel pydantic model for validation

        """

        fields = {}
        pydantic_fields = {}
        annotations = dct.get("__annotations__", {})

        # Extract valid fields from annotations
        for field_name, field_type in annotations.items():
            potential_fields: list[models.Field] = []
            append_to_field = {}
            if isinstance(field_type, str):
                field_type = eval(field_type)
            if get_origin(field_type) is Annotated:
                for metadata in get_args(field_type):
                    if isinstance(metadata, models.Field):
                        # need to copy the field so different field objects are assigned
                        # to different items
                        potential_fields.append(copy_field(metadata))

            dct_value = dct.get(field_name, models.NOT_PROVIDED)
            if isinstance(dct_value, models.Field):
                potential_fields.append(dct_value)
            elif isinstance(dct_value, ExtraKwargs):
                append_to_field |= dct_value.kwargs
            else:
                # assume assigned is default value
                append_to_field["default"] = dct_value

            # if is classvar, let's just move on
            if get_origin(field_type) is ClassVar:
                continue

            if len(potential_fields) == 0:
                raise ValueError(f"No field found for {field_name}")
            elif len(potential_fields) > 1:
                # check all same type
                types = [type(f) for f in potential_fields]
                if len(set(types)) != 1:
                    raise ValueError(
                        f"Multiple fields found for {field_name}, of different types"
                    )
                valid_field = merge_field_instances(potential_fields)
            valid_field = potential_fields[0]
            valid_field.__dict__.update(append_to_field)
            fields[field_name] = valid_field
            default_value = getattr(valid_field, "default", models.NOT_PROVIDED)
            if default_value is not models.NOT_PROVIDED:
                pydantic_fields[field_name] = (
                    pure_pydantic_annotations(field_type),
                    default_value,
                )
            else:
                pydantic_fields[field_name] = pure_pydantic_annotations(field_type)

        dct.update(fields)

        pydantic_fields["model_config"] = ConfigDict(validate_assignment=True)
        pydantic_model_class = create_model(name, **pydantic_fields)
        dct["_inner_pydantic_class"] = pydantic_model_class

        if kwargs:
            dct["Meta"] = type("Meta", (dct.get("Meta", type),), kwargs)
        return super().__new__(cls, name, bases, dct)


class TypedModel(models.Model, metaclass=TypedModelBase, abstract=True):
    _inner_pydantic_class: ClassVar[Type[BaseModel]]

    def __init__(self, *args, **kwargs):
        """
        Validate the item via pydantic but return a django model
        """
        cls = self.__class__
        pydantic_instance = None
        # don't validate when the database creates them
        if len(args) == 0 and len(kwargs) > 0:
            pydantic_instance = cls._inner_pydantic_class(**kwargs)
        super().__init__(*args, **kwargs)
        if pydantic_instance is not None:
            pydantic_instance = cls._inner_pydantic_class.model_construct(
                **model_to_dict(self)
            )
        self._inner_pydantic_instance = pydantic_instance

    def get_pydantic(self) -> BaseModel:
        if not hasattr(self, "_inner_pydantic_instance"):
            raise ValueError("Pydantic instance not created")
        if self._inner_pydantic_instance is None:
            raise ValueError("Pydantic instance is None")
        return self._inner_pydantic_instance

    def __setattr__(self, name: str, value: Any) -> None:
        # keep pydantic and django in sync to trigger validation
        if hasattr(self, "_inner_pydantic_instance"):
            setattr(self._inner_pydantic_instance, name, value)
        return super().__setattr__(name, value)


class TypedUnmanagedModel(TypedModel, abstract=True, managed=False):
    """
    Managed is False - used to connect to existing databases but
    use djangoish syntax
    """
