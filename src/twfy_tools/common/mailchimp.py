import datetime
import hashlib
from functools import lru_cache
from typing import Any, NamedTuple, NewType, Optional, TypedDict

import mailchimp_marketing
import numpy as np
import pandas as pd
import requests
from mailchimp_marketing.api_client import ApiClientError

InternalListID = NewType("InternalListID", str)
InterestInternalId = NewType("InterestInternalId", str)


class MailChimpApiKey(NamedTuple):
    api_key: str
    server: str


class CategoryInfo(NamedTuple):
    group_id: str
    interest_name_to_id: dict[str, InterestInternalId]


class MemberAndInterests(NamedTuple):
    email: str
    interests: list[InterestInternalId]


def get_client(api_key: MailChimpApiKey) -> mailchimp_marketing.Client:  # type: ignore
    """
    Get the mailchimp api client
    """
    client = mailchimp_marketing.Client()
    client.set_config({"api_key": api_key.api_key, "server": api_key.server})
    return client  # type: ignore


@lru_cache
def get_lists(api_key: MailChimpApiKey) -> pd.DataFrame:
    """
    Get dataframe of all lists in the account.
    """
    client = get_client(api_key)
    response: dict[str, Any] = client.lists.get_all_lists(count=1000)
    df = pd.DataFrame(response["lists"])

    # explode the list of dictionaries
    # in the 'stats' column into columns in their own right
    df = pd.concat([df.drop(["stats"], axis=1), df["stats"].apply(pd.Series)], axis=1)
    df = df[["id", "web_id", "name", "member_count"]]
    df = df.sort_values("name")

    return df


def get_recent_email_count(
    api_key: MailChimpApiKey, list_web_id: str, segment_id: str, days: int = 7
) -> int:
    """
    Get the emails and sign up date for a list and segment
    Get the count of the number in the last [x] days
    """
    client = get_client(api_key)

    try:
        list_id = int(list_web_id)
        is_web_id = True
    except ValueError:
        is_web_id = False
    if is_web_id:
        list_id = list_web_id_to_unique_id(api_key, list_web_id)
    else:
        list_id = list_name_to_unique_id(api_key, list_web_id)

    dfs = []
    # paginate until we have all emails
    offset = 0
    while True:
        response: dict[str, Any] = client.lists.get_segment_members_list(
            list_id,
            segment_id,
            count=1000,
            offset=offset,
        )
        df = pd.DataFrame(response["members"])
        dfs.append(df)
        if len(df) < 1000:
            break
        offset += 1000
    df = pd.concat(dfs)  # type: ignore

    # create new timestamp_joined from timestamp_signup and timestamp_opt if timestamp_signup is empty
    df["timestamp_joined"] = np.where(
        df["timestamp_signup"].isna() | df["timestamp_signup"].isin([None, ""]),
        df["timestamp_opt"],  # type: ignore
        df["timestamp_signup"],  # type: ignore
    )
    df["timestamp_joined"] = pd.to_datetime(df["timestamp_joined"]).dt.date
    # get the cutoff date as a date object
    cutoff = (datetime.date.today() - datetime.timedelta(days=days)).isoformat()
    mask: pd.Series[bool] = df["timestamp_joined"].apply(
        lambda x: x.isoformat() > cutoff  # type: ignore
    )
    df = df[mask]
    return len(df)


@lru_cache
def get_segments(api_key: MailChimpApiKey, list_web_id: str) -> pd.DataFrame:
    """
    Get segements of a list as a dataframe
    """
    client = get_client(api_key)
    # if list_web_id can be converted to an int, it's a webid, otherwise it's a name
    try:
        list_id = int(list_web_id)
        is_web_id = True
    except ValueError:
        is_web_id = False
    if is_web_id:
        list_id = list_web_id_to_unique_id(api_key, list_web_id)
    else:
        list_id = list_name_to_unique_id(api_key, list_web_id)
    response: dict[str, Any] = client.lists.list_segments(list_id, count=1000)
    df = pd.DataFrame(response["segments"])  # type: ignore
    df = df[["id", "name", "member_count"]]
    df["id"] = list_web_id + ":" + df["id"].astype(str)
    return df


@lru_cache
def get_recent_campaigns(api_key: MailChimpApiKey, count: int = 20) -> pd.DataFrame:
    """
    Get latest campaigns as a dataframe
    """
    client = get_client(api_key)
    response: dict[str, Any] = client.campaigns.list(
        count=count, sort_field="create_time", sort_dir="DESC"
    )
    df = pd.DataFrame(response["campaigns"])
    df["subject_line"] = df["settings"].apply(lambda x: x.get("subject_line", ""))  # type: ignore
    df["title"] = df["settings"].apply(lambda x: x["title"])  # type: ignore
    df["recipient_count"] = df["recipients"].apply(lambda x: x["recipient_count"])  # type: ignore
    df = df[
        [
            "id",
            "web_id",
            "type",
            "content_type",
            "title",
            "status",
            "send_time",
            "recipient_count",
        ]
    ]

    return df


@lru_cache
def get_templates(api_key: MailChimpApiKey) -> pd.DataFrame:
    """
    Get templates as a dataframe
    """
    client = get_client(api_key)
    response: dict[str, Any] = client.templates.list(count=1000)
    df = pd.DataFrame(response["templates"])
    df = df[
        [
            "id",
            "type",
            "name",
            "date_created",
            "drag_and_drop",
        ]
    ]
    # limit to type user
    df = df[df["type"] == "user"]
    return df


def campaign_web_id_to_unique_id(api_key: MailChimpApiKey, web_id: str) -> str:
    """
    Convert a campaign web id to a campaign id
    """
    df = get_recent_campaigns(1000)
    # convert to web_id, id column dict
    lookup = df.set_index("web_id")["id"].to_dict()
    return lookup[int(web_id)]


def list_web_id_to_unique_id(api_key: MailChimpApiKey, web_id: str) -> str:
    """
    Convert a list web id to a list id
    """
    df = get_lists()
    # convert to web_id, id column dict
    df["web_id"] = df["web_id"].astype(str)
    lookup = df.set_index("web_id")["id"].to_dict()
    return lookup[web_id]


def list_name_to_unique_id(api_key: MailChimpApiKey, name: str) -> InternalListID:
    """
    Convert a list's human name to a unique list id
    """
    df = get_lists(api_key)
    # convert to web_id, id column dict
    df["name"] = df["name"].astype(str)
    lookup = df.set_index("name")["id"].to_dict()
    return lookup[name]


def segment_name_to_unique_id(api_key: MailChimpApiKey, list_id: str, name: str) -> int:
    """
    Convert a segment's human name to a unique segment id
    """
    df = get_segments(list_id)
    # convert to web_id, id column dict
    df["name"] = df["name"].astype(str)
    lookup = df.set_index("name")["id"].to_dict()
    return lookup[name].split(":")[1]


def template_name_to_unique_id(api_key: MailChimpApiKey, name: str) -> int:
    """
    Convert a template's human name to a unique template id
    """
    df = get_templates()
    # convert to web_id, id column dict
    df["name"] = df["name"].astype(str)
    lookup = df.set_index("name")["id"].to_dict()
    return lookup[name]


def send_test_email(
    api_key: MailChimpApiKey, campaign_web_id: str, emails: list[str]
) -> bool:
    """
    send a test email
    """
    client = get_client(api_key)
    campaign_id = campaign_web_id_to_unique_id(api_key, campaign_web_id)
    response: requests.models.Response = client.campaigns.send_test_email(
        campaign_id, {"test_emails": emails, "send_type": "html"}
    )
    # if response code is 200 or 204
    return response.ok


def schedule_campaign(
    api_key: MailChimpApiKey, camapign_web_id: str, schedule_time: datetime.datetime
) -> bool:
    """
    Schedule a campaign
    """
    client = get_client(api_key)
    campaign_id = campaign_web_id_to_unique_id(api_key, camapign_web_id)

    # round to next round 15 minutes (e.g. 15, 30, 45, 60) past hour.
    current_minute = schedule_time.minute
    if current_minute % 15 != 0:
        schedule_time += datetime.timedelta(minutes=15 - (current_minute % 15))
    # delete any seconds or microseconds
    schedule_time = schedule_time.replace(second=0, microsecond=0)

    print(f"Scheduling for {schedule_time} for campaign {campaign_id}")

    str_time = schedule_time.isoformat()
    response: requests.models.Response = client.campaigns.schedule(
        campaign_id,
        {
            "schedule_time": str_time,
            "batch_delivery": False,
        },
    )
    return response.ok


def get_user_hash(email: str):
    # Convert the email to lowercase and get its MD5 hash
    return hashlib.md5(email.lower().encode("utf-8")).hexdigest()


@lru_cache
def get_interest_group(
    api_key: MailChimpApiKey, list_id: InternalListID, interest_group_label: str
) -> CategoryInfo:
    client = get_client(api_key)
    options = client.lists.get_list_interest_categories(list_id)["categories"]
    options = [option for option in options if option["title"] == interest_group_label][
        0
    ]
    category_id = options["id"]
    # get the interests associated with the category
    interests = client.lists.list_interest_category_interests(
        list_id,
        category_id,  # type: ignore
    )["interests"]
    # make lookup from name to id
    interests_by_name = {interest["name"]: interest["id"] for interest in interests}
    return CategoryInfo(category_id, interests_by_name)


def get_member_from_email(
    api_key: MailChimpApiKey, internal_list_id: InternalListID, email: str
) -> dict[str, Any]:
    # Get the member from the list
    client = get_client(api_key)
    user_hash = get_user_hash(email)
    return client.lists.get_list_member(internal_list_id, user_hash)


def get_donor_tags(
    api_key: MailChimpApiKey, internal_list_id: InternalListID, email: str
) -> list[str]:
    # Get the tags for the user
    client = get_client(api_key)
    user_hash = get_user_hash(email)
    details = client.lists.get_list_member_tags(internal_list_id, user_hash)
    return [x["name"] for x in details["tags"]]


def set_donor_tags(
    api_key: MailChimpApiKey,
    internal_list_id: InternalListID,
    email: str,
    tags_to_add: list[str] = [],
    tags_to_remove: list[str] = [],
    disable_automation: bool = False,
):
    client = get_client(api_key)
    # Set the donor status on the user
    user_hash = get_user_hash(email)

    existing_tags = get_donor_tags(api_key, internal_list_id, email)

    tags_to_add = [x for x in tags_to_add if x not in existing_tags]

    to_add_dict = [{"name": tag, "status": "active"} for tag in tags_to_add]
    to_remove_dict = [{"name": tag, "status": "inactive"} for tag in tags_to_remove]
    to_change_list = to_add_dict + to_remove_dict
    if len(to_change_list) == 0:
        return

    details = {
        "tags": to_change_list,
        "is_syncing": disable_automation,
    }
    client.lists.update_list_member_tags(internal_list_id, user_hash, details)


def get_notes(
    api_key: MailChimpApiKey,
    internal_list_id: InternalListID,
    email: str,
) -> list[str]:
    client = get_client(api_key)
    user_hash = get_user_hash(email)
    data = client.lists.get_list_member_notes(internal_list_id, user_hash, count=1000)
    return [x["note"] for x in data["notes"]]


def add_user_notes(
    api_key: MailChimpApiKey,
    internal_list_id: InternalListID,
    email: str,
    notes: list[str],
    check_existing: bool = True,
):
    client = get_client(api_key)
    user_hash = get_user_hash(email)

    if check_existing:
        existing_notes = get_notes(api_key, internal_list_id, email)
        notes_to_add = [note for note in notes if note not in existing_notes]
    else:
        notes_to_add = notes

    for note in notes_to_add:
        client.lists.create_list_member_note(
            internal_list_id, user_hash, {"note": note}
        )


class MemberUpload(TypedDict):
    email: str
    merge_fields: dict[str, Any]


def batch_add_to_interest_group(
    api_key: MailChimpApiKey,
    internal_list_id: InternalListID,
    interest_group_collection: str,
    emails: list[str],
    interests: list[str],
):
    client = get_client(api_key)

    avaliable_list_ids = get_interest_group(
        api_key, internal_list_id, interest_group_collection
    )

    interests_to_add = [
        avaliable_list_ids.interest_name_to_id[interest] for interest in interests
    ]
    interests_to_upload = {x: True for x in interests_to_add}

    # upload all emails in list to audience id
    items = [
        {
            "email_address": x,
            "status": "subscribed",
            "interests": interests_to_upload,
        }
        for x in emails
    ]
    client.lists.batch_list_members(internal_list_id, {"members": items})


def batch_add_to_different_interest_groups(
    api_key: MailChimpApiKey,
    internal_list_id: InternalListID,
    emails_and_interests: list[MemberAndInterests],
    batch_size: int = 200,
):
    """
    Specify *different* interest groups for different emails.
    """
    client = get_client(api_key)

    items: list[dict[str, Any]] = []

    for email, interests in emails_and_interests:
        items.append(
            {
                "email_address": email,
                "status": "subscribed",
                "interests": {x: True for x in interests},
            }
        )

    # upload as batches
    for i in range(0, len(items), batch_size):
        client.lists.batch_list_members(
            internal_list_id, {"members": items[i : i + 200]}
        )


def set_user_metadata(
    api_key: MailChimpApiKey,
    internal_list_id: InternalListID,
    email: str,
    merge_data: dict[str, Any] = {},
    tags: list[str] = [],
    interest_group_collection: Optional[str] = None,
    interests: list[str] = [],
    notes: list[str] = [],
):
    """
    A general purpose function to set metadata for a user.
    If user doesn't exist - we're creating them!
    """
    client = get_client(api_key)
    user_hash = get_user_hash(email)

    try:
        current_person = client.lists.get_list_member(internal_list_id, user_hash)
    except ApiClientError:
        current_person = None

    details: dict[str, Any] = {
        "merge_fields": merge_data,
    }

    if interests:
        avaliable_list_ids = get_interest_group(
            api_key, internal_list_id, interest_group_collection
        )

        interests_to_add = [
            avaliable_list_ids.interest_name_to_id[interest] for interest in interests
        ]

        details["interests"] = {x: True for x in interests_to_add}

    if current_person:
        try:
            client.lists.update_list_member(internal_list_id, user_hash, details)
        except ApiClientError as e:
            print(e.text)
            raise e
        if tags:
            set_donor_tags(api_key, internal_list_id, email, tags_to_add=tags)

    else:
        details["email_address"] = email
        details["status"] = "subscribed"
        if tags:
            details["tags"] = tags
        try:
            client.lists.add_list_member(internal_list_id, details)
        except ApiClientError as e:
            allowed_problems = [
                "looks fake or invalid",
                "Forgotten Email Not Subscribed",
                "Please provide a valid email address",
            ]
            for problem in allowed_problems:
                if problem in e.text:
                    return
            print(e.text)
            raise e

    if notes:
        add_user_notes(api_key, internal_list_id, email, notes=notes)


def get_all_members(
    api_key: MailChimpApiKey,
    internal_list_id: InternalListID,
    cut_off: Optional[int] = None,
) -> list[dict[str, Any]]:
    client = get_client(api_key)

    # Get all the members of the list
    member_count = 1
    running_members: list[dict[str, Any]] = []
    size = 1000 if not cut_off else cut_off
    offset = 0
    while member_count > 0:
        reply = client.lists.get_list_members_info(
            internal_list_id, count=size, offset=0
        )
        running_members.extend(reply["members"])  # type: ignore
        member_count = len(reply["members"])  # type: ignore
        offset += size
        if cut_off and len(running_members) > cut_off:
            break
    return running_members


class MailChimpHandler:
    """
    Shortcut to the mailchimp api to avoid having to remember the api key
    """

    def __init__(self, api_key: str, server: str = "us9"):
        self.api_settings = MailChimpApiKey(api_key, server)

    def get_lists(self) -> pd.DataFrame:
        return get_lists(self.api_settings)

    def list_name_to_unique_id(self, name: str) -> InternalListID:
        return list_name_to_unique_id(self.api_settings, name)

    def segment_name_to_unique_id(self, list_id: str, name: str) -> int:
        return segment_name_to_unique_id(self.api_settings, list_id, name)

    def template_name_to_unique_id(self, name: str) -> int:
        return template_name_to_unique_id(self.api_settings, name)

    def get_segments(self, list_web_id: str) -> pd.DataFrame:
        return get_segments(self.api_settings, list_web_id)

    def get_recent_campaigns(self, count: int = 20) -> pd.DataFrame:
        return get_recent_campaigns(self.api_settings, count)

    def get_templates(self) -> pd.DataFrame:
        return get_templates(self.api_settings)

    def get_interest_group(
        self, list_id: InternalListID, interest_group_label: str
    ) -> CategoryInfo:
        return get_interest_group(self.api_settings, list_id, interest_group_label)

    def get_member_from_email(
        self, internal_list_id: InternalListID, email: str
    ) -> dict[str, Any]:
        return get_member_from_email(self.api_settings, internal_list_id, email)

    def get_donor_tags(self, internal_list_id: InternalListID, email: str) -> list[str]:
        return get_donor_tags(self.api_settings, internal_list_id, email)

    def get_notes(self, internal_list_id: InternalListID, email: str) -> list[str]:
        return get_notes(self.api_settings, internal_list_id, email)

    def get_all_members(
        self, internal_list_id: InternalListID, cut_off: Optional[int] = None
    ) -> list[dict[str, Any]]:
        return get_all_members(self.api_settings, internal_list_id, cut_off)

    def get_user_metadata(
        self, internal_list_id: InternalListID, email: str
    ) -> dict[str, Any]:
        return get_member_from_email(self.api_settings, internal_list_id, email)

    def set_user_metadata(
        self,
        internal_list_id: InternalListID,
        email: str,
        merge_data: dict[str, Any] = {},
        tags: list[str] = [],
        interest_group_collection: Optional[str] = None,
        interests: list[str] = [],
        notes: list[str] = [],
    ):
        set_user_metadata(
            self.api_settings,
            internal_list_id,
            email,
            merge_data,
            tags,
            interest_group_collection,
            interests,
            notes,
        )

    def set_donor_tags(
        self,
        internal_list_id: InternalListID,
        email: str,
        tags_to_add: list[str] = [],
        tags_to_remove: list[str] = [],
        disable_automation: bool = False,
    ):
        set_donor_tags(
            self.api_settings,
            internal_list_id,
            email,
            tags_to_add,
            tags_to_remove,
            disable_automation,
        )

    def add_user_notes(
        self,
        internal_list_id: InternalListID,
        email: str,
        notes: list[str],
        check_existing: bool = True,
    ):
        add_user_notes(
            self.api_settings, internal_list_id, email, notes, check_existing
        )

    def send_test_email(self, campaign_web_id: str, emails: list[str]) -> bool:
        return send_test_email(self.api_settings, campaign_web_id, emails)

    def schedule_campaign(
        self, camapign_web_id: str, schedule_time: datetime.datetime
    ) -> bool:
        return schedule_campaign(self.api_settings, camapign_web_id, schedule_time)

    def batch_add_to_different_interest_groups(
        self,
        internal_list_id: InternalListID,
        emails_and_interests: list[MemberAndInterests],
        batch_size: int = 200,
    ):
        batch_add_to_different_interest_groups(
            self.api_settings, internal_list_id, emails_and_interests, batch_size
        )

    def batch_add_to_interest_group(
        self,
        internal_list_id: InternalListID,
        interest_group_collection: str,
        emails: list[str],
        interests: list[str],
    ):
        batch_add_to_interest_group(
            self.api_settings,
            internal_list_id,
            interest_group_collection,
            emails,
            interests,
        )

    def get_user_hash(self, email: str) -> str:
        return get_user_hash(email)

    def list_web_id_to_unique_id(self, web_id: str) -> str:
        return list_web_id_to_unique_id(self.api_settings, web_id)
