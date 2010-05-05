#!/usr/bin/python2.5 -u
# coding=utf-8

#
# dump_responses.py:
# To send physical paper version of candidate survey.
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

sys.path = ["../", "../google_appengine/"] + sys.path
from google.appengine.ext import db
from google.appengine.ext.remote_api import remote_api_stub
from google.appengine.api.datastore_types import Key

# XXX remember to migrate this when the API becomes stable
from google.appengine.api.labs import taskqueue

from models import Party, Candidate, Seat, Candidacy, RefinedIssue, SurveyResponse

######################################################################
# Helpers

def log(msg):
    print datetime.datetime.now(), msg.encode('utf-8')

# Cache the candidates classes so they can be looked up quicker than one by one later
def lookup_candidates_by_id():
    log("Getting all candidates")
    fs = Candidate.all().fetch(100)
    candidates_by_id = {}
    c = 0
    while fs:
        log("  getting batch from " + str(c))
        for f in fs:
            c = c + 1
            candidates_by_id[str(f.key())] = f
        fs = Candidate.all().filter('__key__ >', fs[-1].key()).fetch(100)
    return candidates_by_id

# Cache the seats classes so they can be looked up quicker than one by one later
def lookup_seats_by_id():
    log("Getting all seats")
    fs = Seat.all().fetch(100)
    seats_by_id = {}
    c = 0
    while fs:
        log("  getting batch from " + str(c))
        for f in fs:
            c = c + 1
            seats_by_id[str(f.key())] = f
        fs = Seat.all().filter('__key__ >', fs[-1].key()).fetch(100)
    return seats_by_id

# Cache the parties classes so they can be looked up quicker than one by one later
def lookup_parties_by_id():
    log("Getting all parties")
    fs = Party.all().fetch(100)
    parties_by_id = {}
    c = 0
    while fs:
        log("  getting batch from " + str(c))
        for f in fs:
            c = c + 1
            parties_by_id[str(f.key())] = f
        fs = Party.all().filter('__key__ >', fs[-1].key()).fetch(100)
    return parties_by_id

# Cache the seats classes so they can be looked up quicker than one by one later
def lookup_issues_by_id():
    log("Getting all issues")
    fs = RefinedIssue.all().fetch(100)
    issues_by_id = {}
    c = 0
    while fs:
        log("  getting batch from " + str(c))
        for f in fs:
            c = c + 1
            issues_by_id[str(f.key())] = f
        fs = RefinedIssue.all().filter('__key__ >', fs[-1].key()).fetch(100)
    return issues_by_id

# Cache the candidacies classes so they can be looked up quicker than one by one later
def lookup_candidacies_by_id():
    log("Getting all candidacies")
    fs = Candidacy.all().fetch(100)
    candidacies_by_id = {}
    c = 0
    while fs:
        log("  getting batch from " + str(c))
        for f in fs:
            c = c + 1
            candidacies_by_id[str(f.key())] = f
        fs = Candidacy.all().filter('__key__ >', fs[-1].key()).fetch(100)
    return candidacies_by_id



# Generator getting all responses
def all_responses():
    log("Getting all responses")
    fs = SurveyResponse.all().fetch(100)
    while fs:
        for f in fs:
            yield f
        fs = SurveyResponse.all().filter('__key__ >', fs[-1].key()).fetch(100)

def put_in_batches(models, limit = 250):
    tot = len(models)
    c = 0
    while len(models) > 0:
        put_models = models[0:limit]
        log("    db.put batch " + str(c) + ", size " + str(len(put_models)) + ", total " + str(tot))
        db.put(put_models)
        models = models[limit:]
        c += 1

######################################################################
# Main

parser = optparse.OptionParser()

parser.set_usage('''Dump info about all responses
''')
parser.add_option('--host', type='string', dest="host", help='domain:port of application, default is localhost:8080. e.g. election.theyworkforyou.com', default="localhost:8080")
parser.add_option('--email', type='string', dest="email", help='email address for authentication to application', default="francis@flourish.org")
parser.add_option('--out', type='string', dest="out", help='CSV file to make')

(options, args) = parser.parse_args()

assert options.out

# Configure connection via remote_api to datastore - after this
# data store calls are remote
log("Connecting to " + options.host)
def auth_func():
    return (options.email, getpass.getpass('Password:'))
remote_api_stub.ConfigureRemoteDatastore('theyworkforyouelection', '/remote_api', auth_func, servername=options.host)

candidates_by_id = lookup_candidates_by_id()
seats_by_id = lookup_seats_by_id()
parties_by_id = lookup_parties_by_id()
issues_by_id = lookup_issues_by_id()
candidacies_by_id = lookup_candidacies_by_id()

# Main loop
h = open(options.out, "wb")
writer = csv.writer(h)
c = 0
writer.writerow(['candidacy_key_name', 'refined_issue_key_name', 'national', 'agreement', 'more_explanation',
    'candidate_name', 'seat_name', 'party_name', 'question', 'reference_url'
    ])

for r in all_responses():
    candidacy_key_str = str(SurveyResponse.candidacy.get_value_for_datastore(r))
    candidacy = candidacies_by_id[candidacy_key_str]

    seat_key_str = str(Candidacy.seat.get_value_for_datastore(candidacy))
    seat = seats_by_id[seat_key_str]

    candidate_key_str = str(Candidacy.candidate.get_value_for_datastore(candidacy))
    candidate = candidates_by_id[candidate_key_str]

    party_key_str = str(Candidate.party.get_value_for_datastore(candidate))
    party = parties_by_id[party_key_str]

    issue_key_str = str(SurveyResponse.refined_issue.get_value_for_datastore(r))
    issue = issues_by_id[issue_key_str]

    row = [ 
            SurveyResponse.candidacy.get_value_for_datastore(r).name(),
            SurveyResponse.refined_issue.get_value_for_datastore(r).name(),
            r.national,
            r.agreement,
            r.more_explanation.encode('utf-8'),
            candidate.name.encode('utf-8'),
            seat.name.encode('utf-8'),
            party.name.encode('utf-8'),
            issue.question.encode('utf-8'),
            issue.reference_url.encode('utf-8'),
    ]
    writer.writerow(row)

h.close()



