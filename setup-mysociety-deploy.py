#!/usr/bin/python2.5

# This script takes requires a more-or-less basic lenny UML root
# filesystem image and sets up TWFY on it.  To create a suitable UML
# root image, run the "create-rootfs.py" script to create an image
# called uml-rootfs-pristine, for example with:
#
#   sudo ./create-rootfs.py 2000 uml-rootfs-pristine tmp mark:mark

from common import *
from testing import *
from subprocess import call, check_call, Popen
import time
import re
import sys
from optparse import OptionParser
from BeautifulSoup import BeautifulSoup
import cgi
from run_main_tests import run_main_tests

check_dependencies()

setup_configuration()

parser = OptionParser(usage="Usage: %prog [OPTIONS]")
parser.add_option('-r', '--reuse-image', dest="reuse", action="store_true",
                  default=False, help="resuse the root fs image instead of starting anew")
parser.add_option('-o', '--output-directory', dest="output_directory",
                  help="override the default test output directory (./output/[TIMESTAMP]/)")
options,args = parser.parse_args()

if len(args) != 0:
    parser.print_help()
    sys.exit(1)

link_command = None

if options.output_directory:
    output_directory = options.output_directory
else:
    output_directory = create_output_directory()

# We switch UML machines frequently, so remove the host key for the
# UML machine's IP address.
remove_host_keys()

# Check if the UML machine is already running:

p = Popen("echo version|uml_mconsole TWFY", stdout=PIPE, shell=True)
console_version = p.communicate()[0]
uml_already_running = re.search('^.TWFY. OK',console_version)

if uml_already_running:
    if not options.reuse:
        print "The UML machine is running, but you haven't specified --reuse-image"
        print "Refusing to overwrite the root filesystem image and exiting.."
        sys.exit(1)
    print "UML machine is already running, not starting a new one."
    if not web_server_working():
        print "... but the web server doesn't seem to be up.  Exiting..."
        sys.exit(1)
else:
    # Restart from standard root filesystem, perhaps generated from
    # create-rootfs.py:
    if not options.reuse:
        check_call(["cp",
                    "-v",
                    "--sparse=always",
                    "uml-rootfs-pristine",
                    "uml-rootfs-test"])
    print "==  UML machine was not running, starting one up."
    uml_stdout = open("uml.stdout","w")
    uml_stderr = open("uml.stderr","w")
    popen_object = Popen("./start-server.py",
                         stdout=uml_stdout,
                         stderr=uml_stderr)
    up = wait_for_web_server(popen_object)
    check_call(["stty","sane"])
    if not up:
        print "Failed to start the UML machine:"
        uml_stdout.close()
        uml_stderr.close()
        print "Standard output was:"
        call("cat uml.stdout",shell=True)
        print "Standard error was:"
        call("cat uml.stderr",shell=True)
        sys.exit(1)

if not user_exists("alice"):
    print "==  Going to try to call adduser"
    if 0 != ssh("adduser --disabled-password --gecos 'An Example User' alice",user="root"):
        raise Exception, "Creating the user alice failed"

alice_ssh_directory = "/home/alice/.ssh"

print "==  Going to test for alice's ssh directory"
if not path_exists_in_uml(alice_ssh_directory):
    if 0 != ssh("mkdir -m 0700 "+alice_ssh_directory,user="root"):
        raise Exception, "Failed to create alice's ssh directory"
    result = ssh("chown alice.alice "+alice_ssh_directory,user="root")

alice_authorized_keys = alice_ssh_directory + "/authorized_keys"

if not path_exists_in_uml(alice_authorized_keys):
    if 0 != scp("id_dsa.alice.pub",alice_authorized_keys,user="root"):
        raise Exception, "Failed to copy over alice's public key"
    if 0 != ssh("chown alice.alice "+alice_authorized_keys,user="root"):
        raise Exception, "Failed to chown alice's authorized_keys file"

# Now that we have ssh authentication set up for both "alice" and
# "root", disable their passwords so we can only log in with ssh and
# public key authentication.  (This sets the encrypted password field
# to '!' in /etc/shadow.)

if 0 != ssh("passwd -l root",user="root"):
    raise Exception, "Locking root's password failed"

if 0 != ssh("passwd -l alice",user="root"):
    raise Exception, "Locking alice's password failed"

if 0 != scp("files-for-uml-deploy/etc/apt/sources.list","/etc/apt/sources.list",user="root"):
        raise Exception, "Copying over the new /etc/apt/sources.list failed"

# Now install some extra packages that we'll need:
if 0 != ssh("apt-get update",user="root"):
    raise Exception, "Updating the package information failed"

if 0 != ssh("DEBIAN_FRONTEND=noninteractive apt-get install --yes locales",user="root"):
    raise Exception, "Installing additional packages failed"

print "==  Checking if we need to generate a UTF-8 locale"
if 0 != ssh("locale -a | egrep -i en_gb.utf-?8",user="root"):
    # The we should generate a UTF-8 locale:
    if 0 != ssh("echo 'en_GB.UTF-8 UTF-8' > /etc/locale.gen",user="root"):
        raise Exception, "Overwriting /etc/locale.gen failed"
    if 0 != ssh("/usr/sbin/locale-gen",user="root"):
        raise Exception, "Running locale-gen failed"

# The packages here should be taken from files added to
# /etc/mysociety/packages.d under the various archetypes, but for the
# moment I'm manually adding these to avoid having to use the private
# CVS:

packages = [ "apache2-suexec",
             "dpkg-dev",
             # maybe pulls in X? "gnuplot",
             "html-helper-mode",
             "iotop",
             "libapache2-mod-fastcgi",
             "libapache2-mod-rpaf",
             "libarchive-tar-perl",
             "libcache-memcached-perl",
             "libclone-perl",
             "libcrypt-openssl-bignum-perl",
             "libcrypt-openssl-dsa-perl",
             "libcrypt-openssl-rsa-perl",
             "libdbd-pg-perl",
             "libdigest-bubblebabble-perl",
             "libdigest-sha-perl",
             "libemail-localdelivery-perl",
             "libfcgi-perl",
             "libfile-slurp-perl",
             "libgeography-nationalgrid-perl",
             "libio-socket-ssl-perl",
             "libio-string-perl",
             "libmime-perl",
             "libnet-dns-perl",
             # not found (?) "libnet-dns-sec-perl",
             # not found (?) "libnet-dns-zonefile-fast-perl",
             "libnetaddr-ip-perl",
             "libpcre3-dev",
             # not found (?) "libregexp-common-dns-perl",
             "libregexp-common-perl",
             # not found (?) "libstatistics-distributions-perl",
             "libsys-hostname-long-perl",
             "libsys-mmap-perl",
             "libunix-mknod-perl",
             "libunix-mknod-perl ",
             "libxml-rss-perl",
             "libxml-twig-perl",
             "locate",
             "mutt",
             "mysql-server",
             "nfs-common",
             "ntp",
             "perl-doc",
             # drags in x11-common "perlmagick",
             "php-elisp",
             "php5-cgi",
             "php5-cli",
             "php5-curl",
             # drags in x11-common "php5-gd",
             "php5-mcrypt",
             "php5-mhash",
             "php5-mysql",
             "php5-mysql ",
             "php5-pgsql",
             "php5-xapian",
             "php5-xdebug",
             "postgresql-8.3",
             "postgresql-client-8.3",
             "python2.5-minimal",
             "rsync",
             "screen",
             "strace",
             "subversion",
             "subversion-tools",
             "sudo",
             "sysstat",
             "tmpreaper",
             "tree" ]

if 0 != ssh("DEBIAN_FRONTEND=noninteractive apt-get install --force-yes --yes "+
            " ".join(packages),user="root"):
    raise Exception, "Installing additional packages failed"

if 0 != ssh("apt-get clean",user="root"):
    raise Exception, "Removing dowloaded packages failed"

directories_to_create = [ "/data/servers/",
                          "/data/vhost/",
                          "/data/servers/archetypes/uml/",
                          "/data/servers/machines/",
                          "/data/servers/vhosts/",
                          "/data/servers/state/",
                          "/etc/mysociety/packages.d/",
                          "/etc/apache2/virtualhosts.d/" ]

if uml_realpath("/var/www") != "/data/vhost":
    if 0 != ssh("mv /var/www /var/www.old",user="root"):
        raise Exception, "Moving the old /var/www out of the way failed"
    if 0 != ssh("ln -sf /data/vhost /var/www",user="root"):
        raise Exception, "Linking /data/vhost to /var/www failed"

# Create /data/servers/ if it doesn't already exist:
if 0 != ssh("mkdir -p "+" ".join(directories_to_create),
             user="root"):
    raise Exception, "Ensuring that required directories exist failed"

# If the mysociety repository doesn't exist, create it:
if not path_exists_in_uml("/data/mysociety/"):
    if 0 != ssh("mkdir -p /data/mysociety/",user="root"):
        raise Exception, "Creating the directory /data/mysociety/ failed"
    if 0 != ssh("( cd /data/mysociety/ && git init )",user="root"):
        raise Exception, "Initializing the git repository in /data/mysociety failed"

existing_environment = os.environ

git_root_environment = existing_environment.copy()
git_root_environment["GIT_SSH"] = "./git-ssh-root"

# Pushing the current mysociety module's version over the to UML machine:
check_call(["git",
            "--git-dir=mysociety/.git/",
            "push",
            "--force",
            "ssh://root@"+configuration['UML_SERVER_IP']+"/data/mysociety/",
            "HEAD:master"],
           env=git_root_environment)

if 0 != ssh("( cd /data/mysociety && git checkout -f master )",user="root"):
    raise Exception, "Checking out the master branch in the UML machine failed"

if 0 != ssh("( cd /data/mysociety && git reset --hard master )",user="root"):
    raise Exception, "Checking out the master branch in the UML machine failed"

# Link the mysociety binary into the PATH:
if 0 != ssh("ln -sf /data/mysociety/bin/mysociety /usr/local/bin/mysociety",user="root"):
    raise Exception, "Creating a link to the mysociety script failed"

# Prevent a series of warnings about creating /root/.cvspass:
if 0 != ssh("touch /root/.cvspass",user="root"):
    raise Exception, "Touching /root/.cvspass failed"

# It seems to some part of deploy expects to write to
# /etc/apache/virtualhosts.d, but that is just a symlink to
# /etc/apache2/virtualhosts.d/ on the mysociety servers so
# create something similar:

# Create /etc/apache/ if it doesn't already exist:
if 0 != ssh("mkdir -p /etc/apache/",user="root"):
    raise Exception, "Ensuring that /etc/apache/ exists failed"

if 0 != ssh("rm -f /etc/apache/virtualhosts.d",user="root"):
    raise Exception, "Failed to remove the old /etc/apache/virtualhosts.d symlink"

if 0 != ssh("ln -sf /etc/apache2/virtualhosts.d /etc/apache/virtualhosts.d",user="root"):
    raise Exception, "Linking the legacy /etc/apache/virtualhosts.d directory to the real one failed"

# Set an arbitrary /etc/mysociety/postgres_secret so we
# don't get errors from pgpw:
if 0 != ssh("echo voilvOvijil4 > /etc/mysociety/postgres_secret",user="root"):
    raise Exception, "Setting /etc/mysociety/postgres_secret failed"

# root needs to be able to ssh to localhost without a passphrase:
if not path_exists_in_uml("/root/.ssh/id_dsa"):
    if 0 != ssh("ssh-keygen -q -t dsa -N '' -f /root/.ssh/id_dsa",user="root"):
        raise Exception, "Generating an ssh keypair for root failed"
    if 0 != ssh("cat /root/.ssh/id_dsa.pub >> /root/.ssh/authorized_keys",user="root"):
        raise Exception, "Adding ssh public key for root to its own authorized_key file failed"

ssh("ssh-keygen -R localhost",user="root")
ssh("ssh-keygen -R 127.0.0.1",user="root")
if 0 != ssh("ssh -i /root/.ssh/id_dsa -o StrictHostKeyChecking=no root@localhost date",user="root"):
    raise Exception, "Getting the right host key in root's known_hosts files failed"

add_passwords_to_configuration()

untemplate_and_rsync("files-for-uml-deploy")

# Restart exim after updating its config:

if 0 != ssh("update-exim4.conf",user="root"):
    raise Exception, "Updating the exim configuration failed"

if 0 != ssh("/etc/init.d/exim4 restart",user="root"):
    raise Exception, "Restarting exim4 failed"

# ------------------------------------------------------------------------

# At least set up the mysql root password and twfy database:

mysql_root_password = pgpw("root")
mysql_twfy_password = pgpw("twfy")

# Check if it's already set:
if 0 != ssh("echo 'show databases;' | mysql -u root --password="+shellquote(mysql_root_password)):
    # Otherwise, maybe this is the first run - try to set it:
    if 0 != ssh("mysqladmin -u root password "+shellquote(mysql_root_password),
                user="root"):
        raise Exception, "Setting the MySQL root password failed"

mysql_root_password_option = " --password="+shellquote(mysql_root_password)

# In case the database already exists, drop it:
ssh("mysqladmin -f -u root"+
    mysql_root_password_option+
    " drop twfy",user="root")

# Create the database:
if 0 != ssh("mysqladmin -u root"+
             mysql_root_password_option+
             " create twfy",user="root"):
    raise Exception, "Creating the twfy database failed"

# Grant all permissions to a 'twfy' user on that database:
if 0 != ssh("echo \"GRANT ALL ON twfy.* TO twfy@localhost IDENTIFIED BY '"+
             mysql_twfy_password+"'\" | "+
             "mysql -u root"+mysql_root_password_option,user="root"):
    raise Exception, "Failed to GRANT ALL on twfy to the twfy MySQL user"

if 0 != ssh("( echo '[client]'; "+
            "echo 'password="+
            mysql_twfy_password+
            "' ) > /home/alice/.my.cnf"):
    raise Exception, "Creating alice's ~/.my.cnf failed"

if 0 != ssh("( echo '[client]'; "+
            "echo 'password="+
            mysql_root_password+
            "' ) > /root/.my.cnf",user="root"):
    raise Exception, "Creating root's ~/.my.cnf failed"

# From this point on, if an exception is thrown, make that into a failed test:

start_all_coverage = uml_date()
ssh("mkdir -p /home/alice/twfy-coverage/")

try:

    # # Call the mysociety-create-databases script to create the appropriate databases:
    # if 0 != ssh("/data/mysociety/bin/mysociety-create-databases",user="root"):
    #     raise Exception, "Creating the databases with mysociety-create-databases failed"

    # Get the schema files:
    ssh_result = ssh("/usr/local/bin/find-schema-files theyworkforyou.sandbox",capture=True)
    if ssh_result.return_value != 0:
        raise Exception, "Finding the database schemas failed"

    schemas = re.split('[\r\n]+',ssh_result.stdout_data)

    i = 0
    for schema_file in schemas:
        if len(schema_file.strip()) > 0:
            run_ssh_test("( cd /data/mysociety && mysql twfy -u twfy < "+schema_file+" )",
                         user="alice",
                         test_name="Creating tables from schema "+schema_file,
                         test_short_name="create-tables-"+str(i))
            i += 1

    # Get the email users:
    ssh_result = ssh("/usr/local/bin/find-email-users theyworkforyou.sandbox",capture=True)
    if ssh_result.return_value != 0:
        raise Exception, "Finding the email users failed"

    for line in re.split('[\r\n]+',ssh_result.stdout_data):
        m = re.search("^([^:]+): (.*)$",line)
        if m:
            username = m.group(1)
            if not user_exists(username):
                print "==  Going to try to call adduser for "+username
                run_ssh_test("adduser --disabled-password --gecos 'Email User From vhosts.pl' "+username,
                             user="root",
                             test_name="Creating user: "+username,
                             test_short_name="create-user-"+username)

    # Run a2enmod:
    run_ssh_test("a2enmod rewrite",
                 user="root",
                 test_name="Enabling mod_rewrite",
                 test_short_name="mod-rewrite")

    # Run a2enmod:
    run_ssh_test("a2enmod suexec",
                 user="root",
                 test_name="Enabling mod_suexec",
                 test_short_name="mod-suexec")

    # Run a2enmod:
    run_ssh_test("a2enmod actions",
                 user="root",
                 test_name="Enabling mod_actions",
                 test_short_name="mod-actions")

    # Now call the standard(ish) deploy scripts:

    run_ssh_test("mysociety -u config --no-check-existing",
                 user="root",
                 test_name="Running mysociety config",
                 test_short_name="mysociety-config")

    run_ssh_test("mysociety -u vhost theyworkforyou.sandbox",
                 user="root",
                 test_name="Running mysociety -u deploy theyworkforyou.sandbox",
                 test_short_name="mysociety-vhost")

    # rsync over some data:

    if 0 != rsync_to_guest("parlparse/","/home/alice/parlparse/",delete=True):
        raise Exception, "Syncing over parlparse failed"

    if 0 != rsync_to_guest("parldata/","/home/alice/parldata/",delete=True):
        raise Exception, "Syncing over parldata failed"

    # Import the member data:

    run_ssh_test("cd /data/vhost/theyworkforyou.sandbox/mysociety/twfy/scripts && ./xml2db.pl --members --from=2009-10-01 --to=2009-10-31",
                 test_name="Importing the member data",
                 test_short_name="import-member-data")

    # Import the rest of the data:

    run_ssh_test("cd /data/vhost/theyworkforyou.sandbox/mysociety/twfy/scripts && ./xml2db.pl --wrans --debates --westminhall --wms --lordsdebates --ni --scotland --scotwrans --scotqs --standing  --from=2009-10-01 --to=2009-10-31",
                 test_name="Importing the rest of the data",
                 test_short_name="import-remaining-data")

    # ========================================================================
    # Now some more usual tests:

    run_main_tests()

except:
    handle_exception(sys.exc_info())

output_report()
