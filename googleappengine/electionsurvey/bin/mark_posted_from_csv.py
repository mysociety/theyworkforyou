#!/usr/bin/python2.5 -u
# coding=utf-8

# Hack as I screwed up

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

# Generator to return all candidacies
def all_candidacies():
    log("Getting all candidacies")
    fs = Candidacy.all().filter("deleted = ", False).fetch(100)
    all = []
    while fs:
        for f in fs:
            all.append(f)
        fs = Candidacy.all().filter("deleted = ", False).filter('__key__ >', fs[-1].key()).fetch(100)
    return all

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

(options, args) = parser.parse_args()

# Configure connection via remote_api to datastore - after this
# data store calls are remote
log("Connecting to " + options.host)
def auth_func():
    return (options.email, getpass.getpass('Password:'))
remote_api_stub.ConfigureRemoteDatastore('theyworkforyouelection', '/remote_api', auth_func, servername=options.host)

# Main loop
reader = csv.reader(open('all-so-far.csv', 'r'))
keys_posted = {}
for row in reader:
    keys_posted[row[0]] = 1


all_candidacies = all_candidacies()
c = 0
for candidacy in all_candidacies:
    print candidacy.key().name()
    posted = False
    if candidacy.key().name() in keys_posted:
        posted = True
    print posted
    candidacy.survey_invite_posted = posted
    c = c + 1

put_in_batches(all_candidacies)




