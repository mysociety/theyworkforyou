#!/bin/sh

PARENT_SCRIPT_URL=https://github.com/mysociety/commonlib/blob/master/bin/install-site.sh

misuse() {
  printf "The variable \033[31m$1\033[0m was not defined, and it should be.\n"
  echo This script should not be run directly - instead, please run:
  echo   $PARENT_SCRIPT_URL
  exit 1
}

# Strictly speaking we don't need to check all of these, but it might
# catch some errors made when changing install-site.sh

[ -z "$DIRECTORY" ] && misuse DIRECTORY
[ -z "$UNIX_USER" ] && misuse UNIX_USER
[ -z "$REPOSITORY" ] && misuse REPOSITORY
[ -z "$REPOSITORY_URL" ] && misuse REPOSITORY_URL
[ -z "$BRANCH" ] && misuse BRANCH
[ -z "$SITE" ] && misuse SITE
[ -z "$DEFAULT_SERVER" ] && misuse DEFAULT_SERVER
[ -z "$HOST" ] && misuse HOST
[ -z "$DISTRIBUTION" ] && misuse DISTRIBUTION
[ -z "$DISTVERSION" ] && misuse DISTVERSION

install_nginx

install_postfix

$REPOSITORY/bin/install-php7-xapian.sh

install_website_packages

# Need a database, isn't in packages for some reason?
echo -n "Installing MySQL... "
DEBIAN_FRONTEND=noninteractive apt-get -qq -y install mysql-server >/dev/null
echo $DONE_MSG
DB_NAME="theyworkforyou"
echo -n "Creating $DB_NAME database... "
mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME; GRANT ALL ON $DB_NAME.* TO '$UNIX_USER'@'localhost'; FLUSH PRIVILEGES;"
echo $DONE_MSG

# Won't work on debian squeeze, only wheezy
echo -n "Installing PHP FPM... "
apt-get -qq install php5-fpm >/dev/null
echo $DONE_MSG

add_website_to_nginx

su -l -c "$REPOSITORY/bin/install-as-user '$UNIX_USER' '$HOST' '$DIRECTORY'" "$UNIX_USER"

if [ $DEFAULT_SERVER = true ] && [ x != x$EC2_HOSTNAME ]
then
    # If we're setting up as the default on an EC2 instance,
    # make sure the ec2-rewrite-conf script is called from
    # /etc/rc.local
    overwrite_rc_local
fi

# Tell the user what to do next:

echo
printf "\033[34mInstallation complete\033[0m - you should now be able to view the site at:\n"
echo   http://$HOST/
echo Or you can run the tests by switching to the "'$UNIX_USER'" user and
echo running [something to be determined]
