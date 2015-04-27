# TheyWorkForYou.com Installation Instructions

## Introduction

This file explains how to install a working version of theyworkforyou.com. I'll
assume you have a basic knowledge of Apache, PHP and MySQL.

## System Requirements

* Perl 5.8.1

* PHP 5.4

    PHP must be configured with curl, using the `--with-curl` option.

* Apache 1.3.28 or 2.2.23

* MySQL 4.1 or 5.0

    Note: it is possible to make the code with 4.0 versions of MySQL, but you
    have to do some mucking around with character sets.
  
* Xapian

    You should be able to run the site without installing Xapian (the search
    engine, see below), but the site might error; if this happens to you,
    contact us so we can make it optional better. If you do have Xapian
    installed, you'll need to make sure PHP includes the Xapian extension by
    adding extension=xapian.so to your php.ini file.

* [Composer](https://getcomposer.org/)

    Composer is used to generate TheyWorkForYou's autoloader, as well as to
    manage some development dependencies. If you are running the site, you can
    generate the required files by running `composer install --no-dev`. If you
    plan on using development tools such as PHPUnit or PHPDoc, you should run
    `composer install` instead.

    Composer will automatically manage as many of its own dependencies as
    possible, but may prompt you if you need to manually install others.

* Other libraries and modules.

You will also need a few other library packages. There is a list, given as the
names of Debian packages, in `theyworkforyou/conf/packages`.

## Other sources of help

Documentation and help is also available from the following:

1. mySociety IRC - http://www.irc.mysociety.org/ - there are generally always
people hanging around willing to help or point you in the right direction.

2. Out of date wiki:

    http://wiki.theyworkforyou.com/cgi-bin/moin.cgi

3. Also look out for announcements on http://www.theyworkforyou.com/
   or http://www.mysociety.org/

## Installation

**Note:**  
I assume you will set this up to run as a *name based virtual host*. It's quite
possible to set it up without using its own virtual host, but I'll not cover
that here.

### Installation Section 1: The Basics

1. Download the latest version of the TheyWorkForYou code from
http://github.com/mysociety/theyworkforyou.

2. Create a new MySQL database:  
  
        mysqladmin -u root -p create twfy

3. Create a user for your database:

        grant all on twfy.* to twfy@localhost identified by 'mypass';

    (to load the Wikipedia titles, you'll need the FILE privilege too, I'm not
    sure if the above line adds it or not).

4. Create database schema. The creation script is in the `db` subdirectory of
the theyworkforyou source code package.  
  
        $ cd db
        $ mysql -u twfy -p twfy < schema.sql

5. Configure the configuration file. In directory `<theyworkforyou>/conf` you
should rename the file `general-example` to `general` and then edit the first
few lines to be appropriate for your setup. Make sure the directories you
specify have the trailing `/`, unless otherwise specified.

6. Configure Apache:
    If you want to create a virtual host, make sure that the NameVirtualHost
    directive is uncommented and says `NameVirtualHost *` and then add something
    like the following, with the paths updated for your setup, and restart
    Apache (`apachectl restart`). The `Include` line is to get the site specific
    commands, like nice rewriting of URLs and so on.  
  
        <VirtualHost localether>
            ServerAdmin me@example.com
            DocumentRoot /data/vhost/www.theyworkforyou.com/theyworkforyou/www/docs
            ServerName twfy.example.com
            <Directory /data/vhost/www.theyworkforyou.com/theyworkforyou/www/docs>
                Options FollowSymLinks
                AllowOverride All
                Allow from all
                Order allow,deny
            </Directory>

            Include /data/vhost/www.theyworkforyou.com/theyworkforyou/conf/httpd.conf

            ErrorLog /var/log/httpd/twfy_error
            CustomLog /var/log/httpd/twfy_access combined
        </VirtualHost>

### Installation Section 2: Getting Some Data

In this section we'll populate your mysql database with lots of nice data about
MPs. This will be based on XML format data published by theyworkforyou.com.

1. Download various data from http://www.theyworkforyou.com/pwdata/

    You need to preserve the directory structure which exists under pwdata, but
    you don't need to get *everything*. Most of the useful stuff is under
    scrapedxml. You'll also need the members directory in the parlparse folder,
    which is available from https://github.com/mysociety/parlparse .
    I recommend either using rsync or downloading the
    zip files containing all the data if you want it - for more information,
    see http://parser.theyworkforyou.com/ under "Getting the Data".

2. Now you have that data, you are ready to import it into your database. You do
this using a script called `xml2db.pl` which is in the `scripts` subdir.

3. Edit the config file (conf/general) to tell it where to look for this data
using the variables `RAWDATA` and `PWMEMBERS`. `PWMEMBERS` should point to the
`members` subdir which you fetched above.

4. now import some data as follows:  

        $ ./xml2db.pl --wrans --debates --lords --all
        $ ./load-people

    That will process all written answers, debates and members. Running the
    script with no args gives usage.

**You should now have a working install of theyworkforyou.com**

## Final notes/known issues

1. Features that don't work on development versions:
    * The Postcode lookup service is disabled on development versions. Instead,
    postcodes are mapped randomly but deterministically to MPs.

2. Search engine:

    The search engine relies uses Xapian (http://www.xapian.org/), which is a
    search toolkit written in C++. In order to use the search system, you need
    to either install the PHP bindings of Xapian from a package, or compile them
    yourself. If you don't have Xapian, you will probably get an error like this
    when you try to do a search:  
  
        Fatal error: Class 'XapianStem' not found in /.../www/includes/easyparliament/searchengine.php on line 39  
There are some old instructions on compiling Xapian for PHP at
http://lists.tartarus.org/pipermail/xapian-discuss/2004-May/000037.html  
  
    You need to run `search/index.pl sincefile` to create the Xapian index. If
    you get an error like this while doing it, you may have run out of disk
    space. You can probably use `search/indexall.sh` to help.

        DBD::mysql::st execute failed: Incorrect key file for table '/tmp/#sql_b63_0.MYI'; try to repair it at ./index.pl line 118.

3. Git Submodules

    If you've downloaded your copy by git cloning, you'll need to pull down the
    submodules in commonlib.

    You can do this by going to the directory where you cloned the repo and run
    `git submodule update --init`

