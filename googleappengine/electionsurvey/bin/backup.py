#!/usr/bin/python2.5
#
# backup.py:
# Backup up data from live site.
#
# Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#

import os
import os.path
import tempfile
import shutil

# Parameters
URL="http://theyworkforyouelection.appspot.com/remote_api"
EMAIL="election@theyworkforyou.com"
#BACKUP_FILE="/data/backups/dailydb/theyworkforyouelection.googleappengine.sqlite3"

DAILYDB_BACKUPS_DIR = '/data/backups/dailydb/'
BACKUP_FILE_NAME = "theyworkforyouelection.googleappengine.sqlite3"
GAE_BACKUP_DIRECTORY = os.path.join(DAILYDB_BACKUPS_DIR, BACKUP_FILE_NAME)

TEMP_BACKUP_DIR = tempfile.mkdtemp(dir=DAILYDB_BACKUPS_DIR)
TMP_BACKUP_FILE_LOCATION = os.path.join(TEMP_BACKUP_DIR, BACKUP_FILE_NAME)

PWD=os.getcwd()
GOOGLE_APPENGINE_PASSWORD=os.getenv("GOOGLE_APPENGINE_PASSWORD")

# Feed it to the uploader
os.chdir('/') # put temporary files here
cmd = '''echo %s | %s/../google_appengine/bulkloader.py --passin --dump --log_file=/tmp/bulkloader-backup-log --db_filename=skip --url=%s --filename=%s --app_id=theyworkforyouelection --email="%s"''' % (GOOGLE_APPENGINE_PASSWORD, PWD, URL, TMP_BACKUP_FILE_LOCATION, EMAIL)
if os.system(cmd) != 0:
    raise Exception("Failed to call bulkloader.py")

# Compress it rsyncably and split into chunks
if os.system("gzip --rsyncable --force %s | split -d -b 256M - %s.gz" %(TMP_BACKUP_FILE_LOCATION, TMP_BACKUP_FILE_LOCATION)) != 0:
    raise Exception("Failed to call gzip --force")

# Remove the old backup directory and replace it with the temporary one.
shutil.rmtree(GAE_BACKUP_DIRECTORY)
shutil.move(TEMP_BACKUP_DIR, GAE_BACKUP_DIRECTORY)
