#!/usr/bin/python2.5
#
# backup.py:
# Backup up data from live site.
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import sys
import csv
import os

# Parameters
URL="http://theyworkforyouelection.appspot.com/remote_api"
EMAIL="election@theyworkforyou.com"
BACKUP_FILE="/data/backups/dailydb/theyworkforyouelection.googleappengine.sqlite3"
PWD=os.getcwd()

# Feed it to the uploader
os.chdir('/') # put temporary files here
cmd = '''%s/../google_appengine/bulkloader.py --dump --log_file=/tmp/bulkloader-backup-log --db_filename=skip --url=%s --filename=%s --app_id=theyworkforyouelection --email="%s"''' % (PWD, URL, BACKUP_FILE, EMAIL)
if os.system(cmd) != 0:
    raise Exception("Failed to call bulkloader.py")

# Compress it
if os.system("gzip --force " + BACKUP_FILE) != 0:
    raise Exception("Failed to call gzip --force")

