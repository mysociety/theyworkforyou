from django.db import connection, transaction
from django.utils.timezone import now

import httpx
import pandas as pd
import rich
from pydantic import BaseModel
from typer import Typer

from twfy_tools.common.config import config
from twfy_tools.db.models import (
    Division,
    PersonDivisionVote,
    Policy,
    PolicyComparisonPeriod,
    PolicyDivisionLink,
    PolicyOrganization,
    PolicyVoteDistribution,
)
from twfy_tools.db.utils import upload_person_info

org_url = config.TWFY_VOTES_URL + "/static/data/organization.parquet"
chambers_url = config.TWFY_VOTES_URL + "/static/data/chambers.parquet"
period_url = config.TWFY_VOTES_URL + "/static/data/policy_comparison_period.parquet"
distribution_url = config.TWFY_VOTES_URL + "/static/data/policy_calc_to_load.parquet"
annotations_url = config.TWFY_VOTES_URL + "/static/data/vote_annotations.parquet"
voting_alignment_url = (
    config.TWFY_VOTES_URL + "/static/data/per_person_party_diff_period.parquet"
)

policies_url = config.TWFY_VOTES_URL + "/policies.json"
policy_json_path = config.RAWDATA / "scrapedjson" / "policies.json"

dev_divisions_url = "https://www.theyworkforyou.com/pwdata/votes/divisions.parquet"
dev_votes_url = "https://www.theyworkforyou.com/pwdata/votes/votes.parquet"


slug_to_twfy_int: dict[str, int] = {
    "commons": 1,
    "lords": 2,
    "ni": 3,
    "scotland": 4,
    "senedd": 5,
}


class BasicAgreement(BaseModel):
    gid: str
    house: str
    strength: str
    url: str
    division_name: str
    date: str
    alignment: str


class PolicyConfig(BaseModel):
    policies: dict[str, str]  # policy id to context_description
    set_descs: dict[str, str]  # set slug to set name
    agreements: dict[
        str, list[BasicAgreement]
    ]  # policy ids to agreements relevant to policies
    sets: dict[str, list[int]]  # set slug and list of policy ids in that slug


class QuietPrint:
    quiet = False

    @classmethod
    def set_quiet(cls, quiet):
        cls.quiet = quiet

    @classmethod
    def print(cls, *args, **kwargs):
        if not cls.quiet:
            rich.print(*args, **kwargs)


rich_print = QuietPrint.print

app = Typer(
    pretty_exceptions_enable=False,
    help=f"CLI tool for loading data from {config.TWFY_VOTES_URL}",
)


def load_policy_comparison_periods():
    """
    Load policy comparison periods from the parquet file
    """
    df = pd.read_parquet(period_url)
    existing = PolicyComparisonPeriod.objects.all()
    slug_to_id = {period.slug: period.id for period in existing}

    to_create: list[PolicyComparisonPeriod] = []
    to_update: list[PolicyComparisonPeriod] = []
    to_delete: list[PolicyComparisonPeriod] = []
    new_slugs: list[str] = []

    for _, row in df.iterrows():
        period = PolicyComparisonPeriod(
            id=slug_to_id.get(row["slug"]),
            slug=row["slug"],
            description=row["description"],
            start_date=row["start_date"],
            end_date=row["end_date"],
            chamber_id=slug_to_twfy_int[row["chamber_slug"]],
        )
        new_slugs.append(row["slug"])
        if period.id:
            to_update.append(period)
        else:
            to_create.append(period)

    for period in existing:
        if period.slug not in new_slugs:
            to_delete.append(period)

    PolicyComparisonPeriod.objects.bulk_create(to_create)
    PolicyComparisonPeriod.objects.bulk_update(
        to_update, fields=["description", "start_date", "end_date", "chamber_id"]
    )
    PolicyComparisonPeriod.objects.filter(
        id__in=[period.id for period in to_delete]
    ).delete()


def load_organisations():
    """
    Load the organizations from the parquet file
    This is effectively the parties in the people.json
    """
    df = pd.read_parquet(org_url)
    existing = PolicyOrganization.objects.all()
    slug_to_id = {org.slug: org.id for org in existing}

    to_create: list[PolicyOrganization] = []
    to_update: list[PolicyOrganization] = []
    to_delete: list[PolicyOrganization] = []
    new_slugs: list[str] = []

    for _, row in df.iterrows():
        org = PolicyOrganization(
            id=slug_to_id.get(row["slug"]),
            slug=row["slug"],
            name=row["name"],
            classification=row["classification"],
        )
        new_slugs.append(row["slug"])
        if org.id:
            to_update.append(org)
        else:
            to_create.append(org)

    for org in existing:
        if org.slug not in new_slugs:
            to_delete.append(org)

    PolicyOrganization.objects.bulk_create(to_create)
    PolicyOrganization.objects.bulk_update(to_update, fields=["name", "classification"])
    PolicyOrganization.objects.filter(id__in=[org.id for org in to_delete]).delete()


def na_to_none(value):
    if pd.isna(value):
        return None
    return value


def load_vote_distribution():
    """
    Load the vote distribution data from the parquet file.
    This contains the alignment of a person to a policy.
    AND the alignment of their party minus them to a policy.
    """
    chamber_id_to_slug = {
        row["id"]: row["slug"] for _, row in pd.read_parquet(chambers_url).iterrows()
    }

    df = pd.read_parquet(distribution_url)

    starting_distribution_count = len(df)

    active_policies = [
        int(x) for x in Policy.objects.all().values_list("policy_id", flat=True)
    ]

    # filter to only active policies
    df = df[df["policy_id"].isin(active_policies)]

    reduced_distribution_count = len(df)

    # reduce
    rich_print(
        f"Reduced vote distribution from [blue]{starting_distribution_count}[/blue] to [blue]{reduced_distribution_count}[/blue] rows"
    )

    to_create: list[PolicyVoteDistribution] = []

    for _, row in df.iterrows():
        distribution = PolicyVoteDistribution(
            id=None,
            policy_id=row["policy_id"],
            person_id=row["person_id"],
            period_id=row["period_id"],
            chamber_id=slug_to_twfy_int[chamber_id_to_slug[row["chamber_id"]]],
            party_id=na_to_none(row["party_id"]),
            is_target=row["is_target"],
            num_votes_same=row["num_votes_same"],
            num_strong_votes_same=row["num_strong_votes_same"],
            num_votes_different=row["num_votes_different"],
            num_strong_votes_different=row["num_strong_votes_different"],
            num_votes_absent=row["num_votes_absent"],
            num_strong_votes_absent=row["num_strong_votes_absent"],
            num_votes_abstain=row["num_votes_abstain"],
            num_strong_votes_abstain=row["num_strong_votes_abstain"],
            num_agreements_same=row["num_agreements_same"],
            num_strong_agreements_same=row["num_strong_agreements_same"],
            num_agreements_different=row["num_agreements_different"],
            num_strong_agreements_different=row["num_strong_agreements_different"],
            start_year=row["start_year"],
            end_year=row["end_year"],
            distance_score=row["distance_score"],
        )
        to_create.append(distribution)

    PolicyVoteDistribution.objects.all().delete()
    rich_print(f"About to load [blue]{len(to_create)}[/blue] vote distributions")
    PolicyVoteDistribution.objects.bulk_create(to_create, batch_size=1000)
    rich_print(f"Loaded [blue]{len(to_create)}[/blue] vote distributions")


def process_policy_json():
    """
    Load the policies from the json file.
    """

    rich_print(f"Loading policies json from {policies_url}")
    data = httpx.get(policies_url, timeout=60).json()

    groups: dict[str, str] = {}

    policies: list[Policy] = []
    policy_to_group: dict[str, list[int]] = {}
    policy_description: dict[str, str] = {}
    division_links: list[PolicyDivisionLink] = []
    agreement_links: dict[str, list[BasicAgreement]] = {}

    # limit to just active policies
    data = [x for x in data if x["status"] == "active"]

    # iterate through policies

    for policy_data in data:
        # we might not end up saving it, but creating a policy obj
        contains_free_vote = False
        if len(policy_data["free_vote_parties"]) > 0:
            contains_free_vote = True
        policy = Policy(
            policy_id=str(policy_data["id"]),
            title=policy_data["name"],
            description=policy_data["policy_description"],
            contains_free_vote=contains_free_vote,
            image="",
            image_attrib="",
            image_license="",
            image_license_url="",
            image_source="",
        )
        policies.append(policy)

        # context descriptions get stored in policies.json
        policy_description[str(policy_data["id"])] = policy_data["context_description"]

        # here we're populating the set name lookup and adding the policy to relevant groups
        for group in policy_data["groups"]:
            groups[group["slug"]] = group["description"]
            if group["slug"] not in policy_to_group:
                policy_to_group[group["slug"]] = []
            policy_to_group[group["slug"]].append(int(policy.policy_id))

        # division links get stored in the database
        for division_link_data in policy_data["division_links"]:
            division_link = PolicyDivisionLink(
                id=None,
                policy_id=policy.policy_id,
                division_id=division_link_data["decision"]["key"],
                lastupdate=now(),
            )
            division_links.append(division_link)

        # agreement links get stored in the json file (very few of them so far)
        for agreement in policy_data["agreement_links"]:
            decision = agreement["decision"]
            # cut off the last part of the decision ref to get the gid
            gid = ".".join(decision["decision_ref"].split(".")[:-1])
            url = f"https://www.theyworkforyou.com/debate/?id={decision['date']}{gid}"

            ba = BasicAgreement(
                gid=decision["date"] + gid,
                house=decision["chamber_slug"],
                strength=agreement["strength"],
                url=url,
                division_name=decision["decision_name"],
                date=decision["date"],
                alignment=agreement["alignment"],
            )
            if policy.policy_id not in agreement_links:
                agreement_links[policy.policy_id] = []
            agreement_links[policy.policy_id].append(ba)

    # only create policies that don't already exist
    existing_policy_ids = list(Policy.objects.values_list("policy_id", flat=True))
    to_create = [
        policy for policy in policies if policy.policy_id not in existing_policy_ids
    ]
    to_update = [
        policy for policy in policies if policy.policy_id in existing_policy_ids
    ]
    # remove any policies that are in the database but not in the json
    # these are likely to be retired policies
    to_delete_ids = [
        policy.policy_id
        for policy in Policy.objects.all()
        if policy.policy_id not in [pol.policy_id for pol in policies]
    ]

    rich_print(f"About to load [blue]{len(to_create)}[/blue] policies")
    Policy.objects.bulk_create(to_create)
    rich_print(f"About to update [blue]{len(to_update)}[/blue] policies")
    Policy.objects.bulk_update(
        to_update, fields=["title", "description", "contains_free_vote"]
    )
    rich_print(f"About to delete [blue]{len(to_delete_ids)}[/blue] policies")
    Policy.objects.filter(policy_id__in=to_delete_ids).delete()

    # not a lot of division links, so delete all and recreate
    rich_print(f"About to load [blue]{len(division_links)}[/blue] division links")
    PolicyDivisionLink.objects.all().delete()
    PolicyDivisionLink.objects.bulk_create(division_links, batch_size=1000)

    # we store a json file with the basic policy config rather than having small tables
    policy_config = PolicyConfig(
        policies=policy_description,
        set_descs=groups,
        agreements=agreement_links,
        sets=policy_to_group,
    )

    rich_print("Saving policy config")
    with policy_json_path.open("w") as f:
        f.write(policy_config.model_dump_json(indent=2))


def load_dev_divisions_votes():
    """
    For dev setup, load the divisions and votes associated with policies.
    (Should be harmless if run accidentally - as will only load what's not present)
    """

    # get divisions not already in our database
    policy_divisions = list(
        PolicyDivisionLink.objects.all().values_list("division_id", flat=True)
    )
    existing_divisions = list(
        Division.objects.all().values_list("division_id", flat=True)
    )
    policy_divisions = [x for x in policy_divisions if x not in existing_divisions]

    df = pd.read_parquet(dev_divisions_url)
    df = df[df["division_id"].isin(policy_divisions)]

    divisions: list[Division] = []

    for _, row in df.iterrows():
        d = Division(
            division_id=row["division_id"],
            division_title=row["division_title"],
            house=row["chamber"],
            gid=row["source_gid"],
            yes_text=row["yes_text"],
            no_text=row["no_text"],
            division_date=row["division_date"],
            division_number=row["division_number"],
            yes_total=row["yes_total"],
            no_total=row["no_total"],
            absent_total=row["absent_total"],
            both_total=row["both_total"],
            majority_vote=row["majority_vote"],
            title_priority=1,
            lastupdate=now(),
        )
        divisions.append(d)

    rich_print(f"About to load [blue]{len(divisions)}[/blue] divisions")
    Division.objects.bulk_create(divisions)

    df = pd.read_parquet(dev_votes_url)

    df = df[df["division_id"].isin(policy_divisions)]

    existing_votes = list(
        PersonDivisionVote.objects.all().values_list("division_id", "person_id")
    )
    df = df[~df[["division_id", "person_id"]].apply(tuple, axis=1).isin(existing_votes)]

    df["proxy"] = df["proxy"].fillna(0)

    votes: list[PersonDivisionVote] = []
    for _, row in df.iterrows():
        vote = PersonDivisionVote(
            division_id=row["division_id"],
            person_id=row["person_id"],
            vote=row["vote"],
            proxy=row["proxy"],
            annotation=row.get("annotation"),
            lastupdate=now(),
        )
        votes.append(vote)

    rich_print(f"About to load [blue]{len(votes)}[/blue] votes")

    # blank out all annotations in PersonDivisionVote table (debug for previous errors)
    PersonDivisionVote.objects.filter(division_id__in=policy_divisions).update(
        annotation=""
    )

    updated = PersonDivisionVote.objects.bulk_create(
        votes, unique_fields=["person_id", "division_id"], batch_size=1000
    )

    rich_print(f"Loaded [blue]{len(updated)}[/blue] votes")


def load_voting_alignment_to_db():
    """
    Load the voting alignment for votes in last year to party_vote_alignment_last_year
    This differs slightly from the 'year by year in twfy-votes' because this is the last 365 days.
    """

    rich_print("Loading voting alignment data")
    df = pd.read_parquet(voting_alignment_url)

    # limit to last year
    df = df[df["in_last_x_year"] == 1]

    items = {}

    for _, row in df.iterrows():
        items[row["person_id"]] = {
            "total_votes": int(row["total_votes"]),
            "avg_diff_from_party": na_to_none(float(row["avg_diff_from_party"])),
        }

    upload_person_info(
        "party_vote_alignment_last_year",
        items,
        remove_absent=True,
        quiet=QuietPrint.quiet,
    )


def process_annotations():
    """
    Load any annotations and add them to the MPs vote.

    This annoyingly needs to use some manual SQL to do an efficent update.

    bulk_update doesn't work because this table doesn't have a single primary key.
    Django's approach for handling unique_together doesn't work for mariaDB.
    Hence: ON DUPLICATE KEY UPDATE
    """
    df = pd.read_parquet(annotations_url)

    # get the values of currently live annotations from the file
    to_update = [
        (int(r["person_id"]), r["division_key"], r.get("link") or "")
        for _, r in df.iterrows()
    ]

    # get all the (person_id, division_id) combos we have annotations for
    combo_keys = []
    for _, r in df.iterrows():
        combo_keys.append((int(r["person_id"]), r["division_key"]))

    # to remove old annotations we need to check all existing annotations
    # to remove those that aren't in the new set
    annotated_votes = PersonDivisionVote.objects.all().exclude(annotation="")
    to_remove = 0
    for vote in annotated_votes:
        if (vote.person_id, vote.division_id) not in combo_keys:
            to_update.append((vote.person_id, vote.division_id, ""))
            to_remove += 1

    rich_print(f"Removing [blue]{to_remove}[/blue] old annotations")

    sql = """
    INSERT INTO persondivisionvotes (person_id, division_id, annotation)
    VALUES (%s, %s, %s)
    ON DUPLICATE KEY UPDATE
        annotation = VALUES(annotation),
        lastupdate = lastupdate;
    """

    BATCH = 1000

    with transaction.atomic():
        with connection.cursor() as cur:
            for i in range(0, len(to_update), BATCH):
                cur.executemany(sql, to_update[i : i + BATCH])

    rich_print(f"Updated [blue]{len(to_update)}[/blue] annotations")

    # check for any annotations we have that aren't in the parquet file


@app.command()
def load_policies(quiet: bool = False):
    """
    Load all policy data into the database and config files
    """
    QuietPrint.set_quiet(quiet)
    rich_print("Loading policy json")
    process_policy_json()
    rich_print("Loading policy comparison periods")
    load_policy_comparison_periods()
    rich_print("Loading organisations")
    load_organisations()
    rich_print("Loading vote distribution")
    load_vote_distribution()


@app.command()
def load_dev_votes(quiet: bool = False):
    QuietPrint.set_quiet(quiet)
    rich_print("Loading dev divisions and votes")
    load_dev_divisions_votes()


@app.command()
def load_voting_alignment(quiet: bool = False):
    """
    Load party alignment (rebelliousness)
    """
    QuietPrint.set_quiet(quiet)
    rich_print("Loading voting alignment")
    load_voting_alignment_to_db()


@app.command()
def load_voting_annotations(quiet: bool = False):
    """
    Load vote annotations
    """
    QuietPrint.set_quiet(quiet)
    rich_print("Loading annotations")
    process_annotations()


if __name__ == "__main__":
    app()
