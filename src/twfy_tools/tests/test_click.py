from click.testing import CliRunner

from twfy_tools.__main__ import app


def test_cli_health():
    runner = CliRunner()
    result = runner.invoke(app, ["health-check", "Text to check for"])

    assert "Text to check for" in result.output
