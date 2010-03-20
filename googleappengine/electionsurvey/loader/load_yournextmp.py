#!/usr/bin/python2.5
#
# yournextmp_load.py:
# Loads data from YourNextMP into GAE
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
EMAIL="a@b.c"
JSON_FILE="moo.json"
JSON_FILE="yournextmp_export_2010-02-23.json"

def upload_model(data, tmp_csvfile, row_names, model, bulkloader):
    # Create CSV file for feeding to GAE bulk uploader
    writer = csv.writer(open(tmp_csvfile, "wb"))
    for k, v in data.iteritems():
        row = [v[x] for x in row_names]
        row = [x and x.encode('utf-8') or None for x in row]
        writer.writerow(row)
    writer.flush()
    #writer.close()
    # Feed it to the uploader
    cmd = '''bulkloader.py --log_file=/tmp/bulkloader-yournextmp-log --db_filename=/tmp/bulkloader-yournextmp-progress --config_file=%s --url=%s --kind=Party --filename=%s --app_id=theyworkforyouelection --email="%s"''' % (bulkloader, URL, tmp_csvfile, EMAIL)
    print cmd
    os.system(cmd)

# Load in JSON file from YNMP
content = open(JSON_FILE).read()
ynmp = json.loads(content)

# Convert to CSV file and feed to GAE
upload_model(ynmp["Party"], "party.csv", ("id", "name", "code", "image_id", "created", "updated"), "Party", "party_loader.py")




