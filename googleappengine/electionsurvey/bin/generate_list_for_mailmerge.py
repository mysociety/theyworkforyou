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
import re

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
    fs = Candidacy.all().filter("deleted = ", False).filter("survey_invite_emailed =", False).filter("survey_invite_posted = ", False).filter("survey_filled_in =", False).fetch(100)
    while fs:
        for f in fs:
            yield f
        fs = Candidacy.all().filter("deleted = ", False).filter("survey_invite_emailed =", False).filter("survey_invite_posted = ", False).filter("survey_filled_in =", False).filter('__key__ >', fs[-1].key()).fetch(100)

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

parser.set_usage('''Find
''')
parser.add_option('--host', type='string', dest="host", help='domain:port of application, default is localhost:8080. e.g. election.theyworkforyou.com', default="localhost:8080")
parser.add_option('--email', type='string', dest="email", help='email address for authentication to application', default="francis@flourish.org")
parser.add_option('--out', type='string', dest="out", help='CSV file to make')
parser.add_option('--real', action='store_true', dest="real", help='Really set the has been posted to flag on candidacies, default is dry run', default=False)

(options, args) = parser.parse_args()

assert options.out

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
h = open(options.out, "wb")
writer = csv.writer(h)
candidates_by_id = lookup_candidates_by_id()
seats_by_id = lookup_seats_by_id()
candidacies = not_invited_candidacies()
candidacies_done = []
c = 0
for candidacy in candidacies:
    c = c + 1

    # This gets the key of a ReferenceProperty, without dereferencing it (so we can
    # look up the already cached candidate model)
    candidate_key_str = str(Candidacy.candidate.get_value_for_datastore(candidacy))
    candidate = candidates_by_id[candidate_key_str]
    seat_key_str = str(Candidacy.seat.get_value_for_datastore(candidacy))
    seat = seats_by_id[seat_key_str]
    #candidate = candidacy.candidate
    #seat = candidacy.seat

    address = candidate.address
    if address != None:
        address = address.strip()
        if address == "":
            address = None
    email = candidate.validated_email()

    msg = str(c) + " Considering " + candidate.name + ", " + seat.name
    if address:
        msg = msg + " current address " + re.sub('\s+', ' ', address)
    if email:
        msg = msg + " current email " + email
    msg = msg + " " + candidate.yournextmp_url()
    log(msg)

    assert not candidacy.survey_invite_emailed 
    assert not candidacy.survey_invite_posted

    if address:
        postcodes = re.findall(r'[A-Z]{1,2}[0-9R][0-9A-Z]?\s*[0-9][A-Z]{2}(?i)', address)
        if len(postcodes) == 1:

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
                candidacies_done.append(candidacy)
            else:
                log(str(c) + "  Yes, it not dry run would mark post sent")
        else:
            log(str(c) + "  No, doesn't have exactly one postcode")
    else:
        log(str(c) + "  No, doesn't have postal address")

    h.flush()

if options.real:
    put_in_batches(candidacies_done)
log("Total done: " + str(c))

h.close()



