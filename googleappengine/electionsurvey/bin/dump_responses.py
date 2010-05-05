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

# Main loop
h = open(options.out, "wb")
writer = csv.writer(h)
c = 0
writer.writerow(['candidacy_key_name', 'refined_issue_key_name', 'national', 'agreement', 'more_explanation'])
for r in all_responses():
    row = [ 
            SurveyResponse.candidacy.get_value_for_datastore(r).name(),
            SurveyResponse.refined_issue.get_value_for_datastore(r).name(),
            r.national,
            r.agreement,
            r.more_explanation.encode('utf-8'),
    ]
    writer.writerow(row)

h.close()



