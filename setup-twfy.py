#!/usr/bin/python2.5

from common import *

# git_url = None
git_url = "git://crumble.dyndns.org/git/mysociety"

# Checkout the mysociety module from mySociety CVS into
# alice's home directory:
if git_url:
    result = ssh("git clone git://crumble.dyndns.org/git/mysociety")
else:
    result = ssh("cvs -d :pserver:anonymous@cvs.mysociety.org:/repos co mysociety")
if result != 0:
    raise Exception, "Checking out the mysociety module from version control failed"

# Create the database schema:


# Copy over some data:


# Restart Apache on the server:
result = ssh("/etc/init.d/apache2 restart",user="root")
if result != 0:
    raise Exception, "Failed to restart Apache on the UML machine."

# Start the tests:
