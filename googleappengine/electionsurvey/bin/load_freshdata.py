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
import optparse
import re
import urllib2
import gzip

sys.path = ["../", "../google_appengine/"] + sys.path
import django.utils.simplejson as json
from google.appengine.ext import db
from google.appengine.ext.remote_api import remote_api_stub
from google.appengine.api.datastore_types import Key

import settings
from models import Party, Candidate, Seat, Candidacy, RefinedIssue

# Parameters
DEMOCLUB_URL="http://www.democracyclub.org.uk/issues/refined_csv"
YOURNEXTMP_URL="http://www.yournextmp.com/data/%s/latest/json_main"

parser = optparse.OptionParser()

parser.set_usage('''Load or update data in TheyWorkForYou election, from YourNextMP and Democracy Club. Arguments are JSON files from YourNextMP or CSV files from Democracy Club to load. If you specify one file of a type, you must specify *all* the files of that type, as other entries in the database will be marked as deleted.''')
parser.add_option('--host', type='string', dest="host", help='domain:port of application, e.g. localhost:8080, election.theyworkforyou.com', default="localhost:8080")
parser.add_option('--email', type='string', dest="email", help='email address for authentication to application', default="francis@flourish.org")
parser.add_option('--fetch', action='store_true', dest='fetch', help='as well as command line arguments, also retrieve latest full dumps from YourNextMP and DemocracyClub and use this', default=False)

(options, args) = parser.parse_args()

for arg in args:
    if not re.search("(\.json|\.csv)$", arg):
        raise Exception("Please only .json or .csv files: " + arg)

######################################################################
# Helpers

def convdate(d):
    return datetime.datetime.strptime(d, "%Y-%m-%dT%H:%M:%S")

def int_or_null(i):
    if i is None:
        return i
    return int(i)

def find_seat(seat_name):
    seat = db.Query(Seat).filter('name =', seat_name).get()
    if not seat and '&' in seat_name:
        seat = db.Query(Seat).filter('name =', seat_name.replace("&", "and")).get()
    if not seat:
        raise Exception("Could not find seat " + seat_name)
    return seat

def log(msg):
    print datetime.datetime.now(), msg

def put_in_batches(models, limit = 500):
    tot = len(models)
    c = 0
    while len(models) > 0:
        put_models = models[0:limit]
        log("    db.put batch " + str(c) + ", size " + str(len(put_models)) + ", total " + str(tot))
        db.put(put_models)
        models = models[limit:]
        c += 1

######################################################################
# Load from YourNextMP

def load_from_ynmp(ynmp):
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
        log("  Storing party " + party.name)
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
        log("  Storing candidate " + candidate.name)
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
        log("  Storing seat " + seat.name)
        seats_by_key[key_name] = seat
    log("Putting all seats")
    put_in_batches(seats_by_key.values())

    # Get list of existing candiacies in remote datastore
    # in batches due to 1000 entity at a time limit, as per http://code.google.com/appengine/articles/remote_api.html
    log("Getting list of Candidacies")
    candidacies = Candidacy.all().filter("deleted =", False).fetch(100)
    to_be_marked_deleted = {}
    while candidacies:
        for candidacy in candidacies:
            key_name = candidacy.key().name()
            log("Marking before have candidacy key " + key_name)
            to_be_marked_deleted[key_name] = candidacy
        candidacies = Candidacy.all().filter("deleted =", False).filter('__key__ >', candidacies[-1].key()).fetch(100)

    # Loop through new dump of candidacies from YourNextMP, adding new ones
    candidacies_by_key = {}
    for candidacy_id, candidacy_data in ynmp["Candidacy"].iteritems():
        key_name = candidacy_data["seat_id"] + "-" + candidacy_data["candidate_id"]

        # find existing entry if there is one, or else make new one
        if key_name in to_be_marked_deleted:
            candidacy = to_be_marked_deleted[key_name]
        else:
            candidacy = Candidacy(key_name = key_name)

        # fill in values
        candidacy.ynmp_id = int(candidacy_id)
        candidacy.seat = seats_by_key[candidacy_data["seat_id"]]
        candidacy.candidate = candidates_by_key[candidacy_data["candidate_id"]]
        candidacy.created = convdate(candidacy_data["created"])
        candidacy.updated = convdate(candidacy_data["updated"])
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

def load_from_democlub(csv_files):
    # Get list of existing refined issues in remote datastore, so can track what to delete
    log("Getting list of refined issues")
    refined_issues = RefinedIssue.all().filter("deleted =", False)
    to_be_marked_deleted = {}
    for refined_issue in refined_issues:
        key_name = refined_issue.key().name()
        log("  Marking before have refined issue key " + key_name)
        to_be_marked_deleted[key_name] = refined_issue

    # Load in CSV file and create/update all the issues
    refined_issues_by_key = {}
    for csv_file in csv_files:
        log("Reading CSV file " + csv_file)
        reader = csv.reader(open(csv_file, "rb"))
        for row in reader:
            (democlub_id, question, reference_url, seat_name, created, updated) = row
            key_name = democlub_id
            refined_issue = RefinedIssue(
                democlub_id = int(democlub_id),
                question = question.decode('utf-8'),
                reference_url = reference_url.decode('utf-8'),
                seat = find_seat(seat_name.decode('utf-8')),
                created = convdate(created),
                updated = convdate(updated),
                key_name = key_name
            )
            log("  Storing local issue for " + seat_name + ": " + refined_issue.question)
            refined_issues_by_key[key_name] = refined_issue

            # record we still have this issue
            if key_name in to_be_marked_deleted:
                del to_be_marked_deleted[key_name]
    log("Putting all refined issues")
    put_in_batches(refined_issues_by_key.values())

    # See which refined issues are left, i.e. are deleted
    for key_name, refined_issue in to_be_marked_deleted.iteritems():
        log("  Marking deleted issue for " + refined_issue.seat.name + ":" + refined_issue.question)
        refined_issue.deleted = True
    log("Putting marked deleted refined issues")
    put_in_batches(to_be_marked_deleted.values())


######################################################################
# Main

# Configure connection via remote_api to datastore - after this
# data store calls are remote
log("Connecting to " + options.host)
def auth_func():
    return (options.email, getpass.getpass('Password:'))
remote_api_stub.ConfigureRemoteDatastore('theyworkforyouelection', '/remote_api', auth_func, servername=options.host)

# Load in extra files
if options.fetch:
    log("Fetching latest Democracy Club CSV file")
    democlub_file = "/tmp/load_freshdata_democracy_club.csv"
    democlub_h = open(democlub_file, 'w')
    democlub_h.write(urllib2.urlopen(DEMOCLUB_URL).read())
    democlub_h.close()
    args.append(democlub_file)
    
    log("Fetching latest YourNextMP JSON file")
    ynmp_url = YOURNEXTMP_URL % (settings.YOURNEXTMP_API_TOKEN)
    ynmp_file = "/tmp/load_freshdata_yournextmp.json"
    ynmp_h = open(ynmp_file + ".gz", 'w')
    ynmp_h.write(urllib2.urlopen(ynmp_url).read())
    ynmp_h.close()
    ynmp_h = open(ynmp_file, 'w')
    ynmp_h.write(gzip.GzipFile(ynmp_file + ".gz").read())
    ynmp_h.close()
    args.append(ynmp_file)
log("File list: " + str(args))

# Load in JSON files, merging as we go
ynmp = {}
for arg in args:
    if re.search("(\.json)$", arg):
        content = open(arg).read()
        json_load = json.loads(content)
        
        for k, v in json_load.iteritems():
            if k in ynmp:
                ynmp[k].update(json_load[k])
            else:
                ynmp[k] = json_load[k]
if ynmp:
    load_from_ynmp(ynmp)

# Get list of CSV files
csv_files = []
for arg in args:
    if re.search("(\.csv)$", arg):
        csv_files.append(arg)
if csv_files:
    load_from_democlub(csv_files)

