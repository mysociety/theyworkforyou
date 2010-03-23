#!/usr/bin/python2.5
#
# load_democracyclub.py:
# Loads data from DemocracyClub into GAE. Call this script as main.
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
CSV_FILE="very-short-refined-issues.csv"
#CSV_FILE="/home/francis/Desktop/refined_local_issues.csv"

# Feed it to the uploader
cmd = '''bulkloader.py --log_file=/tmp/bulkloader-yournextmp-log --db_filename=skip --config_file=%s --url=%s --kind=%s --filename=%s --app_id=theyworkforyouelection --email="%s"''' % ("refined_issue_loader.py", URL, "RefinedIssue", CSV_FILE, EMAIL)
print cmd
os.system(cmd)
