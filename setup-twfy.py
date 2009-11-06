#!/usr/bin/python2.5

from common import *
from subprocess import call, check_call, Popen
import time
import re
import sys

# git_url = None
git_url = "git://crumble.dyndns.org/git/mysociety"

def web_server_working():
    return 0 == call(["curl",
                      "-s",
                      "-f",
                      "http://192.168.2.253",
                      "-o",
                      "/dev/null"])

def wait_for_web_server():
    interval_seconds = 1
    while not web_server_working():
        time.sleep(interval_seconds)

# Restart from standard root filesystem:
if True:
    check_call(["cp",
                "-v",
                "--sparse=always",
                "uml-rootfs-2009-11-06",
                "uml-rootfs-test"])

Popen("./start-server.py")

wait_for_web_server()

# Checkout the mysociety module from mySociety CVS into alice's home
# directory, or if we've specified a git_url, use that instead:
if git_url:
    # If there's a local copy of the repository, just clone that:
    if path_exists_in_uml("/home/alice/mysociety.git/"):
        result = ssh("git clone /home/alice/mysociety.git/ /home/alice/mysociety")
    else:
        result = ssh("git clone git://crumble.dyndns.org/git/mysociety")
else:
    result = ssh("cvs -d :pserver:anonymous@cvs.mysociety.org:/repos co mysociety")
if result != 0:
    raise Exception, "Checking out the mysociety module from version control failed"

# Create the database:
result = ssh("mysqladmin -u root --password="+
             configuration['MYSQL_ROOT_PASSWORD']+
             " create twfy",user="root")
if result != 0:
    raise Exception, "Creating the twfy database failed"

# Grant all permissions to a 'twfy' user on that database:
result = ssh("echo \"GRANT ALL ON twfy.* TO twfy@localhost IDENTIFIED BY '"+
             configuration['MYSQL_TWFY_PASSWORD']+"'\" | "+
             "mysql -u root --password="+
             configuration['MYSQL_ROOT_PASSWORD'])
if result != 0:
    raise Exception, "Failed to GRANT ALL on twfy to the twfy MySQL user"

# Create the database schema:
result = ssh("mysql -u twfy --password="+
             configuration['MYSQL_TWFY_PASSWORD']+
             " twfy < /home/alice/mysociety/twfy/db/schema.sql")
if result != 0:
    raise Exception, "Failed to create the database schema"

# Create the general configuration file from a template:
fp = open("general-template")
template_text = fp.read()
fp.close()
for k in configuration.keys():
    r = re.compile('%'+re.escape(k)+'%')
    template_text = r.sub(configuration[k],template_text)
fp = open("general","w")
fp.write(template_text)
fp.close()

# Copy over the general configuration file:
result = scp("general","/home/alice/mysociety/twfy/conf")
if result != 0:
    raise Exception, "Failed to scp the general configuration file"

# Copy over some data:


# Run a2ensite:
result = ssh("a2ensite twfy",user="root")
if result != 0:
    raise Exception, "Making the site available failed."

# Restart Apache on the server:
result = ssh("/etc/init.d/apache2 reload",user="root")
if result != 0:
    raise Exception, "Failed to restart Apache on the UML machine."

# Start the tests:
