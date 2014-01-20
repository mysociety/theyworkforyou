#!/usr/bin/env bash

# Install everything
sudo apt-get install -qq apache2

# Configure Apache
WEBROOT="$(pwd)"
CGIROOT=`dirname "$(which php-cgi)"`
echo "WEBROOT: $WEBROOT"
echo "CGIROOT: $CGIROOT"
sudo echo "<VirtualHost *:80>
        DocumentRoot $WEBROOT/www/docs
        ServerName theyworkforyou.dev

        Include $WEBROOT/conf/httpd.conf

        # Configure PHP as CGI
        ScriptAlias /local-bin $CGIROOT
        DirectoryIndex index.php
        AddType application/x-httpd-php5 .php
        Action application/x-httpd-php5 '/local-bin/php-cgi'

</VirtualHost>" | sudo tee /etc/apache2/sites-available/default > /dev/null
cat /etc/apache2/sites-available/default

sudo a2enmod rewrite
sudo a2enmod actions
sudo service apache2 restart

# Configure custom domain
echo "127.0.0.1 theyworkforyou.dev" | sudo tee --append /etc/hosts

echo "TRAVIS_PHP_VERSION: $TRAVIS_PHP_VERSION"
