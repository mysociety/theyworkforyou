#!/usr/bin/python2.5 -u
# coding=utf-8

#
# make_csv_for_exim_mailout.py:
# Dumps data to use to send reminder mails to candidates using Exim.
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

sys.path = ["../", "../google_appengine/"] + sys.path
from google.appengine.ext import db
from google.appengine.ext.remote_api import remote_api_stub
from google.appengine.api.datastore_types import Key

# XXX remember to migrate this when the API becomes stable
from google.appengine.api.labs import taskqueue

from models import Party, Candidate, Seat, Candidacy, RefinedIssue

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


# Generator to return all candidacies
def emailed_not_filled_in_candidacies():
    log("Getting all candidacies")
    fs = Candidacy.all().filter("survey_invite_emailed =", True).filter("survey_filled_in = ", False).fetch(100)
    while fs:
        for f in fs:
            yield f
        fs = Candidacy.all().filter("survey_invite_emailed =", True).filter("survey_filled_in = ", False).filter('__key__ >', fs[-1].key()).fetch(100)

######################################################################
# Main

parser = optparse.OptionParser()

parser.set_usage('''Find
''')
parser.add_option('--host', type='string', dest="host", help='domain:port of application, default is localhost:8080. e.g. election.theyworkforyou.com', default="localhost:8080")
parser.add_option('--email', type='string', dest="email", help='email address for authentication to application', default="francis@flourish.org")
parser.add_option('--out', type='string', dest="out", help='CSV file to make')

(options, args) = parser.parse_args()

# Configure connection via remote_api to datastore - after this
# data store calls are remote
log("Connecting to " + options.host)
def auth_func():
    return (options.email, getpass.getpass('Password:'))
remote_api_stub.ConfigureRemoteDatastore('theyworkforyouelection', '/remote_api', auth_func, servername=options.host)

# Main loop
assert options.out
writer = csv.writer(open(options.out, "wb"))
candidates_by_id = lookup_candidates_by_id()
seats_by_id = lookup_seats_by_id()
candidacies = emailed_not_filled_in_candidacies()
c = 0
for candidacy in candidacies:
    # This gets the key of a ReferenceProperty, without dereferencing it (so we can
    # look up the already cached candidate model)
    candidate_key_str = str(Candidacy.candidate.get_value_for_datastore(candidacy))
    candidate = candidates_by_id[candidate_key_str]
    seat_key_str = str(Candidacy.seat.get_value_for_datastore(candidacy))
    seat = seats_by_id[seat_key_str]
    #candidate = candidacy.candidate
    #seat = candidacy.seat

    assert not candidacy.survey_filled_in 
    assert candidacy.survey_invite_emailed 

    c = c + 1
    log(str(c) + " Dumping " + candidate.name + " current email " + str(candidate.validated_email()))

    url = "http://election.theyworkforyou.com/survey/" + candidacy.survey_token
    row = [candidate.name.encode("utf-8"), candidate.validated_email(), url, seat.name.encode("utf-8")]

    writer.writerow(row)




