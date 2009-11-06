#!/usr/bin/python2.5

# This script takes requires a more-or-less basic lenny UML root
# filesystem image and sets up TWFY on it.
#
# The customizations that were needed above a basic lenny
# installation are:
#
#   /etc/network/interfaces:
#     - e.g. adding something like:
#         auto eth0
#         iface eth0 inet static
#           address 192.168.2.253
#           netmask 255.255.255.0
#           gateway 192.168.2.254
#       to set up the TUN/TAP networking
#
#   /etc/resolv.conf:
#     - Set up with your nameservers
#
#   /etc/fstab:
#     - As usual for UML, you need to mount the kernel
#       modules, so add a line like:
#
#   Adding the SSH public keys for alice and root to their
#   authorized_keys files.
#
#   /etc/apache2/sites-available/twfy
#
#     - This will be a2ensite-ed later, but should look something
#       like:
#
#         <VirtualHost *:81>
#                 ServerAdmin mark-twfyuml@longair.net
#                 DocumentRoot /home/alice/mysociety/twfy/www/docs
#                 <Directory /home/alice/mysociety/twfy/www/docs/>
#                         Options FollowSymLinks
#                         AllowOverride All
#                         allow from all
#                         Order allow,deny
#                 </Directory>
#                 ErrorLog /var/log/apache2/error.log
#                 LogLevel warn
#                 CustomLog /var/log/apache2/access.log combined
#                 Include /home/alice/mysociety/twfy/conf/httpd.conf
#         </VirtualHost>
#
#   /etc/apache2/ports.conf
#
#     - Add these lines:
#         NameVirtualHost *:81
#         Listen 81
#
#   Install the right set of packages.
#
# Ideally in the future it would be nice to be able to generate
# the inital image completely from the Debian debootstrap system
# and making these modifications by hand.  This means that you
# don't have to distribute a 1GiB UML root filesystem image.  It
# will also make it easier to run the same script to set up on
# an Amazon EC2 instance.

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
