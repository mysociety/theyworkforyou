Hello everyone,

If you're looking to set up a new Parliamentary monitoring site then you
should look at our Pombola probject at
http://www.mysociety.org/international/pombola/ which takes the lessons
we've learned from writing and running TheyWorkForYou and uses them to
create a modern, flexible and more easily adaptable platform for
creating your own Parliamentary monitoring site. We strongly encourage
people to use this rather than trying to adapt TheyWorkForYou to their
own requirements.

If you want to dig in to the source of TheyWorkForYou then carry right
on below.

We're pleased to release the TheyWorkForYou.com source code. This is the code
for the website itself. It's mainly written in PHP, although there are also some
Perl scripts for database loading and indexing. The backend parser code is
separate and available from the UKParse Subversion repository on KnowledgeForge.

The TheyWorkForYou.com source code in this distribution is released under a BSD
style license. Roughly, this means you are free to copy, use, modify and
redistribute the code or binaries made from the code. Commercial or non-
commercial use is allowed. However, we disclaim warranty, and expect you not to
use our name without our permission.

See the file LICENSE.txt for exact legal information.

## What is TheyWorkForYou.com anyway?

Everything MPs say in the UK's House of Commons is recorded in a document called
Hansard. TheyWorkForYou.com helps make sense of this vital democratic resource
and, crucially, allows you to add your own annotations and links to the official
transcripts of Parliament.

## How on earth do I use this code?

See INSTALL.md for installation questions.

If you have questions, the best place to ask is the mySociety TheyWorkForYou email list at https://groups.google.com/a/mysociety.org/forum/#!forum/theyworkforyou

### Developing with Vagrant

You will need the latest versions of VirtualBox and Vagrant, then:

* Stick an entry in your `hosts` file to point `theyworkforyou.dev` at `192.168.88.10` (Apache doesn't like IP addresses).
* Run `vagrant up`.
* Go make a cup of tea. It may take a while whilst Vagrant and Puppet do their thing.
* Point your web browser at `http://theyworkforyou.dev` and marvel at modern technology.

#### Compiling Static Assets

If you're working on a page which uses the redesign, you will need to compile
static assets after changes:

* `vagrant ssh`
* `cd /data/twfy/www/docs/style`
* `compass compile` for a one-off compilation or `compass watch` to recompile on changes

## Testing

TheyWorkForYou includes a (currently *very limited*) test suite, using PHPunit.
To run tests, ensure that the environment variables `TWFY_TEST_DB_HOST`,
`TWFY_TEST_DB_NAME`, `TWFY_TEST_DB_USER` and `TWFY_TEST_DB_PASS` are set and
contain relevant information for your testing database. The database will be
stripped down and rebuilt during testing, so make sure it's not an important
copy.

You may find that in some versions of PHPUnit errors are thrown regarding code
coverage reports. If this is the case, the version installed by [Composer](http://getcomposer.org/)
and located at `/vendor/bin/phpunit` should run correctly.

## Build Status

[![Build Status](https://img.shields.io/travis/mysociety/theyworkforyou.svg)](https://travis-ci.org/mysociety/theyworkforyou)

[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/mysociety/theyworkforyou.svg)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)

[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/mysociety/theyworkforyou.svg)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)

[![mySociety Installability](http://img.shields.io/badge/installability-bronze-8c7853.svg)](http://mysociety.github.io/installation-standards.html)
