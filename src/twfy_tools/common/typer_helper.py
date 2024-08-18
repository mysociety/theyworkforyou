"""
Configure Typer app with submodules and click commands
"""

from typing import NamedTuple

import click
from trogon import Trogon
from typer import Typer
from typer.main import get_group


class TyperModule(NamedTuple):
    module: Typer
    name: str


class MysocTyper(Typer):
    def add_submodules(
        self, typer_groups: list[TyperModule] = [], click_groups: list[click.Group] = []
    ) -> click.Group:
        for module in typer_groups:
            self.add_typer(module.module, name=module.name)

        click_app = get_group(self)
        for module in click_groups:
            click_app.add_command(module)

        @click_app.command()
        @click.pass_context
        def ui(ctx: click.Context):
            """
            Open terminal UI
            """
            Trogon(click_app, click_context=ctx).run()

        return click_app
