#!/usr/bin/python2.5

# This script takes requires a more-or-less basic lenny UML root
# filesystem image and sets up TWFY on it.  To create a suitable UML
# root image, run the "create-rootfs.py" script to create an image
# called uml-rootfs-pristine, for example with:
#
#   sudo ./create-rootfs.py 1200 uml-rootfs-pristine tmp mark:mark

from common import *
from subprocess import call, check_call, Popen
import time
import re
import sys
from optparse import OptionParser

parser = OptionParser(usage="Usage: %prog [OPTIONS]")
parser.add_option('-r', '--reuse-image', dest="reuse", action="store_true",
                  default=False, help="resuse the root fs image instead of starting anew")
parser.add_option('-o', '--output-directory', dest="output_directory",
                  help="override the default test output directory (./output/[TIMESTAMP]/)")
options,args = parser.parse_args()

if len(args) != 0:
    parser.print_help()
    sys.exit(1)

# git_url = None
git_url = "git://crumble.dyndns.org/git/mysociety"

if options.output_directory:
    output_directory = options.output_directory
else:
    iso_time = time.strftime("%Y-%m-%dT%H:%M:%S",time.gmtime())
    output_directory = "output/%s/" % (iso_time,)

check_call(["mkdir","-p",output_directory])

# Restart from standard root filesystem, perhaps generated from
# create-rootfs.py:
if not options.reuse:
    check_call(["cp",
                "-v",
                "--sparse=always",
                "uml-rootfs-pristine",
                "uml-rootfs-test"])

initial_mysql_root_password = ""
if False:
    initial_mysql_root_password += " --password="+configuration['MYSQL_ROOT_PASSWORD']

# We switch UML machines frequently, so remove the host key for the
# UML machine's IP address.
check_call(["ssh-keygen","-R",configuration['UML_SERVER_IP']])

uml_stdout = open(output_directory+"/uml.stdout","w")
uml_stderr = open(output_directory+"/uml.stderr","w")

popen_object = Popen("./start-server.py",
                     stdout=uml_stdout,
                     stderr=uml_stderr)

wait_for_web_server_or_exit(popen_object.pid)

check_call(["stty","sane"])

if not user_exists("alice"):
    print "==  Going to try to call adduser"
    result = ssh("adduser --disabled-password --gecos 'An Example User' alice",user="root")
    if result != 0:
        raise Exception, "Failed to create the user alice"

alice_ssh_directory = "/home/alice/.ssh"

print "==  Going to test for alice's ssh directory"
if not path_exists_in_uml(alice_ssh_directory):
    result = ssh("mkdir -m 0700 "+alice_ssh_directory,user="root")
    if result != 0:
        raise Exception, "Failed to create alice's ssh directory"
    result = ssh("chown alice.alice "+alice_ssh_directory,user="root")

alice_authorized_keys = alice_ssh_directory + "/authorized_keys"

if not path_exists_in_uml(alice_authorized_keys):
    result = scp("id_dsa.alice.pub",alice_authorized_keys,user="root")
    if result != 0:
        raise Exception, "Failed to copy over alice's public key"
    result = ssh("chown alice.alice "+alice_authorized_keys,user="root")
    if result != 0:
        raise Exception, "Failed to chown alice's authorized_keys file"

# Now install some extra packages that we'll need:
result = ssh("apt-get update",user="root")
if result != 0:
    raise Exception, "Updating the package information failed"

result = ssh("DEBIAN_FRONTEND=noninteractive apt-get install --yes locales",user="root")
if result != 0:
    raise Exception, "Installing additional packages failed"

result = ssh("echo 'en_GB.UTF-8 UTF-8' > /etc/locale.gen",user="root")
if result != 0:
    raise Exception, "Overwriting /etc/locale.gen failed"

result = ssh("/usr/sbin/locale-gen",user="root")
if result != 0:
    raise Exception, "Running locale-gen failed"

result = ssh("DEBIAN_FRONTEND=noninteractive apt-get install --yes mysql-server php5-curl php5-mysql php5-xdebug subversion rsync python2.5-minimal",user="root")
if result != 0:
    raise Exception, "Installing additional packages failed"

result = ssh("apt-get clean",user="root")
if result != 0:
    raise Exception, "Removing dowloaded packages failed"

# Checkout the mysociety module from mySociety CVS into alice's home
# directory, or if we've specified a git_url, use that instead:
if not path_exists_in_uml("/home/alice/mysociety"):
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

# In case the database already exists, drop it:
ssh("mysqladmin -f -u root"+
    initial_mysql_root_password+
    " drop twfy",user="root")

# Create the database:
result = ssh("mysqladmin -u root"+
             initial_mysql_root_password+
             " create twfy",user="root")
if result != 0:
    raise Exception, "Creating the twfy database failed"

# Grant all permissions to a 'twfy' user on that database:
result = ssh("echo \"GRANT ALL ON twfy.* TO twfy@localhost IDENTIFIED BY '"+
             configuration['MYSQL_TWFY_PASSWORD']+"'\" | "+
             "mysql -u root"+initial_mysql_root_password)
if result != 0:
    raise Exception, "Failed to GRANT ALL on twfy to the twfy MySQL user"

# Create the database schema:
result = ssh("mysql -u twfy --password="+
             configuration['MYSQL_TWFY_PASSWORD']+
             " twfy < /home/alice/mysociety/twfy/db/schema.sql")
if result != 0:
    raise Exception, "Failed to create the database schema"

# Create the general configuration file from a template:
untemplate("general.template","general")

# Copy over the general configuration file:
result = scp("general","/home/alice/mysociety/twfy/conf")
if result != 0:
    raise Exception, "Failed to scp the general configuration file"

# Create a world-writable directory for coverage data:
coverage_directory = "/home/alice/twfy-coverage/"
if not path_exists_in_uml(coverage_directory):
    result = ssh("mkdir -m 0777 "+coverage_directory)
    if result != 0:
        raise Exception, "Failed to create coverage data directory"

# Remove any old data from that directory:
result = ssh("rm -f "+coverage_directory+"/*")
if result != 0:
    raise Exception, "Failed to clean the coverage data directory"

# Copy over the instrument.php file:
result = scp("instrument.php",
             "/home/alice/mysociety/twfy/www/includes/instrument.php")
if result != 0:
    raise Exception, "Failed to copy over the instrument.php file"

instrument_script = "/usr/local/bin/add-php-instrumentation.py"

# Copy over the script to add instrumentation file:
result = scp("add-php-instrumentation.py",
             instrument_script,
             user="root")
if result != 0:
    raise Exception, "Failed to copy over the add-php-instrumentation.py file"

# Make it executable:
result = ssh("chmod a+rx "+instrument_script,user="root")
if result != 0:
    raise Exception, "Failed to make the add-php-instrumentation.py file executable"

# Add the instrumentation:
ssh_result = ssh(instrument_script+" /home/alice/mysociety/twfy/www/",capture=True)
if ssh_result.return_value != 0:
    raise Exception, "Instrumenting the TWFY PHP code failed."

instrumented_files = re.split('\s+',ssh_result.stdout_data)
print "File list:"
for i in instrumented_files:
    print "  "+i

result = ssh("cd ~/mysociety/ && git checkout -b instrumented")
if result != 0:
    raise Exception, "Failed to create a new branch for the instrumented version"

result = ssh("cd ~/mysociety/twfy/www && git add includes/instrument.php "+" ".join(instrumented_files))
if result != 0:
    raise Exception, "Failed to add the instrumented files to the index"

result = ssh("cd ~/mysociety/ && git commit -m 'An instrumented version of the TWFY code'")
if result != 0:
    raise Exception, "Creating a new commit failed."

# Copy over some data:


# Set up the Apache virtual host:
result = scp("etc-apache2-sites-available-twfy",
             "/etc/apache2/sites-available/twfy",
             user="root")
if result != 0:
    raise Exception, "Failed to copy over the VirtualHost configuration"

result = scp("etc-apache2-ports-conf",
             "/etc/apache2/ports.conf",
             user="root")
if result != 0:
    raise Exception, "Failed to copy over the ports.conf file"

# Run a2enmod:
result = ssh("a2enmod rewrite",user="root")
if result != 0:
    raise Exception, "Enabling the rewrite module failed"

# Run a2ensite:
result = ssh("a2ensite twfy",user="root")
if result != 0:
    raise Exception, "Making the site available failed"

# Restart Apache on the server:
result = ssh("/etc/init.d/apache2 reload",user="root")
if result != 0:
    raise Exception, "Failed to restart Apache on the UML machine."

# Start the tests:
