from .common.typer_helper import MysocTyper, TyperModule
from .utils import division_io

app = MysocTyper()

app = app.add_submodules(typer_groups=[TyperModule(division_io.app, "divisions-io")])

if __name__ == "__main__":
    app()
