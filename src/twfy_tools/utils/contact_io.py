"""
Interface to upload user optin information to MailChimp.
"""

from datetime import date, timedelta
from functools import lru_cache

from typer import Typer

from twfy_tools.common.config import config
from twfy_tools.common.mailchimp import (
    InterestInternalId,
    MailChimpHandler,
    MemberAndInterests,
)
from twfy_tools.db.models import OptinValues, User

app = Typer()

mailing_list_name = "mySociety Newsletters"


@lru_cache
def get_mailing_list_internal_id():
    client = MailChimpHandler(config.MAILCHIMP_API_KEY)
    return client.list_name_to_unique_id(mailing_list_name)


def get_internal_optin_id(
    interest_group: str, interest_name: str
) -> InterestInternalId:
    client = MailChimpHandler(config.MAILCHIMP_API_KEY)

    # get internal id for the mailing list
    mailing_list_id = get_mailing_list_internal_id()
    interest_group_items = client.get_interest_group(mailing_list_id, interest_group)
    return interest_group_items.interest_name_to_id[interest_name]


def upload_contacts(start_date: date, end_date: date):
    """
    Given a start and end date - get the optin_values for new users.
    And add them to the relevant mySociety lists.
    """
    optin_interest_lookup = {
        OptinValues.OPTIN_ORG: get_internal_optin_id(
            interest_group="What are you interested in? Select all that apply",
            interest_name="mySociety newsletter",
        ),
        OptinValues.OPTIN_STREAM: get_internal_optin_id(
            interest_group="What are you interested in? Select all that apply",
            interest_name="Democracy and Parliaments",
        ),
        OptinValues.OPTIN_SERVICE: get_internal_optin_id(
            interest_group="Service interest",
            interest_name="TheyWorkForYou",
        ),
    }

    new_users = User.objects.filter(
        registrationtime__gte=start_date, registrationtime__lt=end_date
    )

    members_and_values: list[MemberAndInterests] = []

    for user in new_users:
        internal_ids = [optin_interest_lookup[x] for x in user.get_optin_values()]
        if internal_ids:
            members_and_values.append(MemberAndInterests(user.email, internal_ids))

    client = MailChimpHandler(config.MAILCHIMP_API_KEY)
    mailing_list_id = get_mailing_list_internal_id()

    client.batch_add_to_different_interest_groups(mailing_list_id, members_and_values)

    print(f"Uploaded {len(members_and_values)} users to MailChimp")


@app.command()
def upload_mailchimp_optins(start_date: str, end_date: str):
    """
    Upload the optin values for new users to MailChimp.
    """
    upload_contacts(date.fromisoformat(start_date), date.fromisoformat(end_date))


@app.command()
def upload_yesterday():
    """
    Uploads the optin values for users who registered yesterday.
    the end_date is a less than, so safe to say today.
    """
    yesterday = date.today() - timedelta(days=1)
    upload_contacts(yesterday, date.today())


if __name__ == "__main__":
    app()
