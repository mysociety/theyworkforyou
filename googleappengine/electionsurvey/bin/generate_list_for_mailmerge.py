#!/usr/bin/python2.5 -u
# coding=utf-8

#
# generate_list_for_mailmerge.py:
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
def not_invited_candidacies():
    log("Getting all candidacies")
    fs = Candidacy.all().filter("deleted = ", False).filter("survey_invite_emailed =", False).filter("survey_invite_posted = ", False).fetch(100)
    while fs:
        for f in fs:
            yield f
        fs = Candidacy.all().filter("deleted = ", False).filter("survey_invite_emailed =", False).filter("survey_invite_posted = ", False).filter('__key__ >', fs[-1].key()).fetch(100)

######################################################################
# Main

parser = optparse.OptionParser()

parser.set_usage('''Find
''')
parser.add_option('--host', type='string', dest="host", help='domain:port of application, default is localhost:8080. e.g. election.theyworkforyou.com', default="localhost:8080")
parser.add_option('--email', type='string', dest="email", help='email address for authentication to application', default="francis@flourish.org")
parser.add_option('--out', type='string', dest="out", help='CSV file to make')
parser.add_option('--real', action='store_true', dest="real", help='Really queue the emails, default is dry run', default=False)

(options, args) = parser.parse_args()

if options.real:
    log("Real run, will mark as postal sent")
else:
    log("Dry run only, will not mark as postal sent")

# Configure connection via remote_api to datastore - after this
# data store calls are remote
log("Connecting to " + options.host)
def auth_func():
    return (options.email, getpass.getpass('Password:'))
remote_api_stub.ConfigureRemoteDatastore('theyworkforyouelection', '/remote_api', auth_func, servername=options.host)

# Main loop
assert options.out
h = open(options.out, "wb")
writer = csv.writer(h)
#candidates_by_id = lookup_candidates_by_id()
#seats_by_id = lookup_seats_by_id()
candidacies = not_invited_candidacies()
c = 0
for candidacy in candidacies:
    # This gets the key of a ReferenceProperty, without dereferencing it (so we can
    # look up the already cached candidate model)
    #candidate_key_str = str(Candidacy.candidate.get_value_for_datastore(candidacy))
    #candidate = candidates_by_id[candidate_key_str]
    #seat_key_str = str(Candidacy.seat.get_value_for_datastore(candidacy))
    #seat = seats_by_id[seat_key_str]
    candidate = candidacy.candidate
    seat = candidacy.seat

    assert not candidacy.survey_filled_in 
    assert not candidacy.survey_invite_emailed 
    assert not candidacy.survey_invite_posted

    address = candidate.address
    if address != None:
        address = address.strip()
        if address == "":
            address = None

    msg = str(c) + " Considering " + candidate.name 
    if address:
        msg = msg + " current address " + address
    log(msg)

    if address:
        c = c + 1

        row = [ 
                candidacy.key().name(),
                candidate.name.encode("utf-8"), 
                seat.name.encode("utf-8"),
                candidacy.survey_token.encode("utf-8"), 
                candidate.address.encode("utf-8")
        ]
        writer.writerow(row)

        if options.real:
            log(str(c) + "  Yes, marking post sent")
            candidacy.survey_invite_posted = True
            candidacy.survey_invite_sent_to_addresses.append(address)
            candidacy.put()
        else:
            log(str(c) + "  Yes, it not dry run would mark post sent")

    h.flush()

h.close()



