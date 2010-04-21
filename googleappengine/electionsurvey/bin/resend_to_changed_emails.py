#!/usr/bin/python2.5 -u
# coding=utf-8

#
# resend_to_changed_emails.py:
# Sets it up as if not send to changed emails.
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

# Generator to return all candidacies
def emailed_candidacies():
    log("Getting all candidacies")
    fs = Candidacy.all().filter("survey_invite_emailed =", True).fetch(100)
    while fs:
        for f in fs:
            yield f
        fs = Candidacy.all().filter("survey_invite_emailed =", True).filter('__key__ >', fs[-1].key()).fetch(100)

######################################################################
# Main

parser = optparse.OptionParser()

parser.set_usage('''Find
''')
parser.add_option('--real', action='store_true', dest="real", help='Really make the changes', default=False)
parser.add_option('--host', type='string', dest="host", help='domain:port of application, default is localhost:8080. e.g. election.theyworkforyou.com', default="localhost:8080")
parser.add_option('--email', type='string', dest="email", help='email address for authentication to application', default="francis@flourish.org")

(options, args) = parser.parse_args()

if options.real:
    log("Real run, will trigger actual email sends")
else:
    log("Dry run only, no candidates will be marked not sent")

# Configure connection via remote_api to datastore - after this
# data store calls are remote
log("Connecting to " + options.host)
def auth_func():
    return (options.email, getpass.getpass('Password:'))
remote_api_stub.ConfigureRemoteDatastore('theyworkforyouelection', '/remote_api', auth_func, servername=options.host)

# Main loop
candidates_by_id = lookup_candidates_by_id()
candidacies = emailed_candidacies()
c = 0
for candidacy in candidacies:
    # This gets the key of a ReferenceProperty, without dereferencing it (so we can
    # look up the already cached candidate model)
    candidate_key_str = str(Candidacy.candidate.get_value_for_datastore(candidacy))
    candidate = candidates_by_id[candidate_key_str]

    c = c + 1
    log(str(c) + " Considering " + candidate.name + " current email " + str(candidate.email) + ", already sent to: " + ",".join(candidacy.survey_invite_sent_to_emails))
    if candidate.email not in candidacy.survey_invite_sent_to_emails:
        if candidacy.survey_filled_in:
            log("  *** already filled in survey but email has changed")
        else:
            if options.real:
                log("  *** setting survey_invite_emailed to False")
                candidacy.survey_invite_emailed = False
                candidacy.put()
            else:
                log("  *** would have set survey_invite_emailed to False")



