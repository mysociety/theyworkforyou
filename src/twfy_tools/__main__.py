from .common.typer_helper import MysocTyper, TyperModule
from .utils import contact_upload, division_io

app = MysocTyper()


@app.command()
def health_check(input_value: str):
    """
    Health check endpoint
    """
    print(f"Health check: {input_value}")


app = app.add_submodules(
    typer_groups=[
        TyperModule(division_io.app, "divisions-io"),
        TyperModule(contact_upload.app, "contact-upload"),
    ]
)
if __name__ == "__main__":
    app()
