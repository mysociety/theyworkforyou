#!/usr/bin/python2.5
#
# load_freshdata.py:
# Loads data from YourNextMP and DemocracyClub into GAE. Call this script as
# main.
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import sys
import csv
import os
import getpass
import datetime

sys.path = ["../", "../google_appengine/"] + sys.path
import django.utils.simplejson as json
from google.appengine.ext import db
from google.appengine.ext.remote_api import remote_api_stub
from google.appengine.api.datastore_types import Key

from models import Party, Candidate, Seat, Candidacy, RefinedIssue

# Parameters
HOST="localhost:8080"
#HOST="election.theyworkforyou.com"
EMAIL="francis@flourish.org"
YOURNEXTMP_JSON_FILE="very-short-candidates-sample.json"
#YOURNEXTMP_JSON_FILE="yournextmp_export_2010-02-23.json"
DEMOCRACYCLUB_LOCAL_ISSUE_CSV_FILE="very-short-refined-issues.csv"

######################################################################
# Helpers

def convdate(d):
    return datetime.datetime.strptime(d, "%Y-%m-%dT%H:%M:%S")

def int_or_null(i):
    if i is None:
        return i
    return int(i)

def find_seat(x):
    seats = list(db.Query(Seat).filter('name =', x))
    if len(seats) == 0:
        raise Exception("Could not find seat: " + x)
    assert len(seats) == 1
    return seats[0]

def log(msg):
    print datetime.datetime.now(), msg

def put_in_batches(models, limit = 500):
    c = 0
    while len(models) > 0:
        put_models = models[0:limit]
        log("    db.put batch " + str(c) + ", size " + str(len(put_models)))
        db.put(put_models)
        models = models[limit:]
        c += 1

######################################################################
# Main

# Configure connection via remote_api to datastore - after this
# data store calls are remote
def auth_func():
    return (EMAIL, getpass.getpass('Password:'))
remote_api_stub.ConfigureRemoteDatastore('theyworkforyouelection', '/remote_api', auth_func, servername=HOST)
log("Connected to " + HOST)

######################################################################
# Load from YourNextMP

# Load in JSON file from YNMP
content = open(YOURNEXTMP_JSON_FILE).read()
ynmp = json.loads(content)

# Put parties in datastore - don't worry about deleted ones, they just
# won't be referenced by other tables.
parties_by_key = {}
for party_id, party_data in ynmp["Party"].iteritems():
    key_name = party_id
    party = Party(
        ynmp_id = int(party_id),
        name = party_data["name"],
        code = party_data["code"],
        image_id = int_or_null(party_data["image_id"]),
        created = convdate(party_data["created"]),
        updated = convdate(party_data["updated"]),
        key_name = key_name
    )
    log("Storing party " + party.name)
    parties_by_key[key_name] = party
log("Putting all parties")
put_in_batches(parties_by_key.values())

# Put candidates in datastore - don't worry about deleted ones, they
# just won't be referenced by a candidacy
candidates_by_key = {}
for candidate_id, candidate_data in ynmp["Candidate"].iteritems():
    key_name = candidate_id
    candidate = Candidate(
        ynmp_id = int(candidate_id),
        name = candidate_data["name"],
        code = candidate_data["code"],
        email = candidate_data["email"],
        party = parties_by_key[candidate_data["party_id"]],
        image_id = int_or_null(candidate_data["image_id"]),
        created = convdate(candidate_data["created"]),
        updated = convdate(candidate_data["updated"]),
        key_name = key_name
    )
    log("Storing candidate " + candidate.name)
    candidates_by_key[key_name] = candidate
log("Putting all candidates")
put_in_batches(candidates_by_key.values())

# Put seats in datastore - don't worry about deleted ones, they
# just won't be referenced by a candidacy
seats_by_key = {}
for seat_id, seat_data in ynmp["Seat"].iteritems():
    key_name = seat_id
    seat = Seat(
        ynmp_id = int(seat_id),
        name = seat_data["name"],
        code = seat_data["code"],
        created = convdate(seat_data["created"]),
        updated = convdate(seat_data["updated"]),
        key_name = key_name
    )
    log("Storing seat " + seat.name)
    seats_by_key[key_name] = seat
log("Putting all seats")
put_in_batches(seats_by_key.values())

# Get list of existing candiacies in remote datastore
log("Getting list of Candidacies")
candidacies = Candidacy.all().filter("deleted =", False)
to_be_marked_deleted = {}
for candidacy in candidacies:
    key_name = candidacy.key().name()
    log("Marking before have candidacy key " + key_name)
    to_be_marked_deleted[key_name] = candidacy

# Loop through new dump of candidacies from YourNextMP, adding new ones
candidacies_by_key = {}
for candidacy_id, candidacy_data in ynmp["Candidacy"].iteritems():
    key_name = candidacy_data["seat_id"] + "-" + candidacy_data["candidate_id"]
    # use get_or_insert so we don't wipe out other data fields in Candidacy
    # (such as survey_token, audit_log etc.)
    candidacy = Candidacy.get_or_insert(key_name)
    candidacy.ynmp_id = int(candidacy_id)
    candidacy.seat = seats_by_key[candidacy_data["seat_id"]]
    candidacy.candidate = candidates_by_key[candidacy_data["candidate_id"]]
    candidacy.created = convdate(candidacy_data["created"])
    candidacy.updated = convdate(candidacy_data["updated"])
    candidacy.key_name = key_name
    candidacy.deleted = False
    log("Storing candidacy " + candidacy.seat.name + " " + candidacy.candidate.name)
    candidacies_by_key[key_name] = candidacy

    # record we still have this candidacy
    if key_name in to_be_marked_deleted:
        del to_be_marked_deleted[key_name]
log("Putting all candidacies")
put_in_batches(candidacies_by_key.values())

# See which candidacies are left, i.e. are deleted
for key_name, candidacy in to_be_marked_deleted.iteritems():
    log("Marking deleted " + candidacy.seat.name + " " + candidacy.candidate.name)
    candidacy.deleted = True
log("Putting marked deleted candidacies")
put_in_batches(to_be_marked_deleted.values())

######################################################################
# Load from DemocracyClub

# Get list of existing refined issues in remote datastore, so can track what to delete
log("Getting list of refined issues")
refined_issues = RefinedIssue.all().filter("deleted =", False)
to_be_marked_deleted = {}
for refined_issue in refined_issues:
    key_name = refined_issue.key().name()
    log("Marking before have refined issue key " + key_name)
    to_be_marked_deleted[key_name] = refined_issue

# Load in CSV file and create/update all the issues
reader = csv.reader(open(DEMOCRACYCLUB_LOCAL_ISSUE_CSV_FILE, "rb"))
refined_issues_by_key = {}
for row in reader:
    (democlub_id, question, reference_url, seat_name, created, updated) = row
    key_name = democlub_id
    refined_issue = RefinedIssue(
        democlub_id = int(democlub_id),
        question = question,
        reference_url = reference_url,
        seat = find_seat(seat_name),
        created = convdate(created),
        updated = convdate(updated),
        key_name = key_name
    )
    log("Storing local issue for " + seat_name + ": " + refined_issue.question)
    refined_issues_by_key[key_name] = refined_issue

    # record we still have this issue
    if key_name in to_be_marked_deleted:
        del to_be_marked_deleted[key_name]
log("Putting all refined issues")
put_in_batches(refined_issues_by_key.values())

# See which refined issues are left, i.e. are deleted
for key_name, refined_issue in to_be_marked_deleted.iteritems():
    log("Marking deleted issue for " + refined_issue.seat.name + ":" + refined_issue.question)
    refined_issue.deleted = True
log("Putting marked deleted refined issues")
put_in_batches(to_be_marked_deleted.values())





