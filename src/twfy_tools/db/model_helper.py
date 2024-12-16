from typing import Any, Callable, TypeVar

from django.db import models

from typing_extensions import ParamSpec, dataclass_transform

FieldType = TypeVar(
    "FieldType",
    bound=models.Field,
)
P = ParamSpec("P")


def field(
    model_class: Callable[P, FieldType],
    null: bool = False,
    *args: P.args,
    **kwargs: P.kwargs,
) -> Any:
    """
    Helper function for basic field creation.
    So the type checker doesn't complain about the return type
    and you can specify the specify type of the item as a typehint.
    """
    if args:
        raise ValueError("Positional arguments are not supported")
    kwargs["null"] = null
    if isinstance(model_class, type) and issubclass(model_class, models.Field):
        return model_class(**kwargs)
    else:
        raise ValueError(f"Invalid model class {model_class}")


@dataclass_transform(kw_only_default=True, field_specifiers=(field,))
class DataclassModelBase(models.base.ModelBase):
    def __new__(cls, name: str, bases: tuple[type], dct: dict[str, Any], **kwargs: Any):
        """
        Basic metaclass to make class keyword parameters into a Meta class.

        e.g. (as below) - abstract is passed in as a class keyword parameter
        rather than a `class Meta: abstract = True` block.

        """
        if kwargs:
            dct["Meta"] = type("Meta", (dct.get("Meta", type),), kwargs)
        return super().__new__(cls, name, bases, dct)


class DataclassModel(models.Model, metaclass=DataclassModelBase, abstract=True):
    """
    Basic wrapper that adds tidier metaclass config, and dataclass
    prompting for IDEs.
    """


class UnmanagedDataclassModel(DataclassModel, managed=False, abstract=True):
    """
    Dataclass model that is not managed by the django schema.
    """
