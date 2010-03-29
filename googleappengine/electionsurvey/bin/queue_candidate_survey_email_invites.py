#!/usr/bin/python2.5
#
# queue_candidate_survey_email_invites.py:
# Sets up tasks to invite specfied sets of candidates
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
    print datetime.datetime.now(), msg

######################################################################
# Main

parser = optparse.OptionParser()

parser.set_usage('''Queue tasks to email a bunch of candidates with an invitation to do the survey.''')
parser.add_option('--constituency', type='string', dest="constituency", help='Name of constituency, default is all constituencies', default=None)
parser.add_option('--limit', type='int', dest="limit", help='Maximum number to queue', default=None)
parser.add_option('--real', action='store_true', dest="real", help='Really queue the emails, default is dry run', default=False)
parser.add_option('--host', type='string', dest="host", help='domain:port of application, default is localhost:8080. e.g. election.theyworkforyou.com', default="localhost:8080")
parser.add_option('--email', type='string', dest="email", help='email address for authentication to application', default="francis@flourish.org")

(options, args) = parser.parse_args()

if options.real:
    log("Real run, will trigger actual email sends")
else:
    log("Dry run only, no emails will be triggered")

# Configure connection via remote_api to datastore - after this
# data store calls are remote
def auth_func():
    return (options.email, getpass.getpass('Password:'))
remote_api_stub.ConfigureRemoteDatastore('theyworkforyouelection', '/remote_api', auth_func, servername=options.host)
log("Connected to " + options.host)

candidacies = db.Query(Candidacy)
candidacies.filter("deleted = ", False)
candidacies.filter("survey_invite_emailed = ", False)

if options.constituency != None:
    seat = db.Query(Seat).filter("name =", options.constituency).get()
    if not seat:
        raise Exception("Constituency not found")
    candidacies.filter("seat = ", seat.key().name())

if options.limit != None:
    candidacies = candidacies.fetch(int(options.limit))

log("Found " + str(candidacies.count(None)) + " candidacies")
c = 0
for candidacy in candidacies:
    if candidacy.candidate.validated_email():
        c += 1 # one second between sending each mail
        if options.real:
            log(str(c) + " queued invite for candidacy " + candidacy.seat.name + ", " + candidacy.candidate.name)
            eta = datetime.datetime.utcnow() + datetime.timedelta(seconds=c) # AppEngine servers use UTC
            taskqueue.add(url='/task/invite_candidacy_survey/' + str(candidacy.key().name()), eta = eta)
        else:
            log(str(c) + " would queue invite for candidacy " + candidacy.seat.name + ", " + candidacy.candidate.name)




