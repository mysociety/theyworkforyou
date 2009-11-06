#!/usr/bin/python2.5

from common import *
from subprocess import call, check_call, Popen
import time

# git_url = None
git_url = "git://crumble.dyndns.org/git/mysociety"

def web_server_working():
    return 0 != call(["curl",
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
check_call(["echo","cp",
            "-v",
            "--sparse=always",
            "uml-rootfs-2009-11-06",
            "uml-rootfs-test"])

Popen("./start-server.py")

wait_for_web_server()

# Checkout the mysociety module from mySociety CVS into
# alice's home directory:
if git_url:
    result = ssh("git clone git://crumble.dyndns.org/git/mysociety")
else:
    result = ssh("cvs -d :pserver:anonymous@cvs.mysociety.org:/repos co mysociety")
if result != 0:
    raise Exception, "Checking out the mysociety module from version control failed"

# Create the database:
result = ssh("mysqladmin -u root -p create twfy",user="root")
if result != 0:
    raise Exception, "Creating the twfy database failed"

# Create the database schema:


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
