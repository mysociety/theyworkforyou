from .common.typer_helper import MysocTyper, TyperModule
from .utils import division_io

app = MysocTyper()


@app.command()
def health_check(input_value: str):
    """
    Health check endpoint
    """
    print(f"Health check: {input_value}")


app = app.add_submodules(typer_groups=[TyperModule(division_io.app, "divisions-io")])

if __name__ == "__main__":
    app()
