#!/usr/bin/env python3
# encoding: utf-8

import json
import os
import sys
import re
import MySQLdb

# Set up commonlib pylib
package_dir = os.path.abspath(os.path.split(__file__)[0])
sys.path.append(os.path.normpath(package_dir + "/../commonlib/pylib"))

# And from that, get the config
from mysociety import config
config.set_file(os.path.abspath(package_dir + "/../conf/general"))

filename = os.path.normpath(config.get('BASEDIR') + config.get('IMAGEPATH') + 'attribution.json')
try:
    data = json.load(open(filename))
except OSError:
    # Do not care if no file present
    sys.exit(0)

db_connection = MySQLdb.connect(
    host=config.get('TWFY_DB_HOST'),
    db=config.get('TWFY_DB_NAME'),
    user=config.get('TWFY_DB_USER'),
    passwd=config.get('TWFY_DB_PASS'),
    charset='utf8',
)
db_cursor = db_connection.cursor()

data_blank = [r for r in data if not r["data_value"]]
data_blank = [(r["person_id"], r["data_key"]) for r in data_blank]
db_cursor.executemany("""DELETE FROM personinfo
    WHERE person_id=%s AND data_key=%s""", data_blank)

data = [r for r in data if r["data_value"]]
data = [(r["person_id"], r["data_key"], r["data_value"]) for r in data]
db_cursor.executemany("""INSERT INTO personinfo
    (person_id, data_key, data_value) VALUES (%s, %s, %s)
    ON DUPLICATE KEY UPDATE data_value = VALUES(data_value)""", data)

db_connection.commit()
