#!/usr/bin/python2.5
#
# yournextmp_load.py:
# Loads data from YourNextMP into GAE. Call this script as main.
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import sys
import csv
import os

sys.path.append("../")
import django.utils.simplejson as json

# Parameters
URL="http://localhost:8080/remote_api"
#URL="http://theyworkforyouelection.appspot.com/remote_api"
EMAIL="francis@flourish.org"
JSON_FILE="very-short-sample.json"
#JSON_FILE="yournextmp_export_2010-02-23.json"

def upload_model(data, tmp_csvfile, row_names, model, bulkloader):
    # Create CSV file for feeding to GAE bulk uploader
    f = open(tmp_csvfile, "wb")
    writer = csv.writer(f)
    for k, v in data.iteritems():
        row = [v[x] for x in row_names]
        row = [x and x.encode('utf-8') or None for x in row]
        writer.writerow(row)
    f.close()
    # Feed it to the uploader
    cmd = '''bulkloader.py --log_file=/tmp/bulkloader-yournextmp-log --db_filename=skip --config_file=%s --url=%s --kind=%s --filename=%s --app_id=theyworkforyouelection --email="%s"''' % (bulkloader, URL, model, tmp_csvfile, EMAIL)
    print cmd
    os.system(cmd)

# Load in JSON file from YNMP
content = open(JSON_FILE).read()
ynmp = json.loads(content)

# Convert to CSV file and feed to GAE
upload_model(ynmp["Party"], "party.csv", ("id", "name", "code", "image_id", "created", "updated"), "Party", "party_loader.py")
upload_model(ynmp["Candidate"], "candidate.csv", ("id", "name", "code", "email", "party_id", "image_id", "created", "updated"), "Candidate", "candidate_loader.py")
upload_model(ynmp["Seat"], "seat.csv", ("id", "name", "code", "created", "updated"), "Seat", "seat_loader.py")
upload_model(ynmp["Candidacy"], "candidacy.csv", ("id", "seat_id", "candidate_id", "created", "updated"), "Candidacy", "candidacy_loader.py")




