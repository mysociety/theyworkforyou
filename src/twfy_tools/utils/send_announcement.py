"""
CLI tool to send MailChimp announcement campaigns.
"""

from enum import Enum

from typer import Typer

from twfy_tools.common.config import config
from twfy_tools.common.mailchimp import MailChimpHandler

app = Typer()


class AnnouncementCampaign(str, Enum):
    new_commons_register = "new-commons-register"


CAMPAIGN_NAMES: dict[AnnouncementCampaign, str] = {
    AnnouncementCampaign.new_commons_register: "announcement - new commons register",
}


@app.command()
def send(campaign: AnnouncementCampaign):
    """
    Send a MailChimp announcement campaign.
    """
    campaign_title = CAMPAIGN_NAMES[campaign]
    client = MailChimpHandler(config.MAILCHIMP_API_KEY)

    try:
        campaign_id = client.campaign_name_to_unique_id(campaign_title)
    except KeyError:
        print(f"Error: Campaign '{campaign_title}' not found in MailChimp")
        raise SystemExit(1)

    print(f"Found campaign '{campaign_title}' with id {campaign_id}")

    try:
        client.send_campaign(campaign_id)
    except Exception as e:
        print(f"Error: Failed to send campaign '{campaign_title}': {e}")
        raise SystemExit(1)

    print(f"Campaign '{campaign_title}' sent successfully")


if __name__ == "__main__":
    app()
