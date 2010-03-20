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

URL="http://localhost:8080/remote_api"
EMAIL="a@b.c"
JSON_FILE="moo.json"
JSON_FILE="yournextmp_export_2010-02-23.json"


sys.path.append("../")

import django.utils.simplejson as json

content = open(JSON_FILE).read()
ynmp = json.loads(content)


def upload_model(data, tmp_csvfile, row_names, model, bulkloader):
    # Create CSV file for feeding to GAE bulk uploader
    writer = csv.writer(open(tmp_csvfile, "wb"))
    for k, v in data.iteritems():
        row = [v[x] for x in row_names]
        row = [x and x.encode('utf-8') or None for x in row]
        writer.writerow(row)
    # Feed it to the uploader
    os.system('''bulkloader.py --config_file=%s --url=%s --kind=Party --filename=%s --app_id=theyworkforyouelection --email="%s"''' % (bulkloader, URL, tmp_csvfile, EMAIL))


upload_model(ynmp["Party"], "party.csv", ("id", "name", "code", "image_id", "created", "updated"), "Party", "party_loader.py")
