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
separate and available [on GitHub](https://github.com/mysociety/parlparse). You
can read more about this on
[TheyWorkForYou's parser info page](http://parser.theyworkforyou.com/parser.html)

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

#### Xapian

TheyWorkForYou uses Xapian to provide search. You will need to [install it
manually](http://trac.xapian.org/wiki/FAQ/PHP%20Bindings%20Package). Since Vagrant
runs PHP 5.4 you will need to use the second (test-surpressing) build command.

## Testing

TheyWorkForYou includes a (currently limited) unit and acceptance test suite,
using [Codeception](http://codeception.com/).

The easiest way to run tests is to install the Vagrant environment, then run the
following commands to install the test tools and configure the test environment:

* `vagrant ssh`
* `cd /data/twfy`
* `composer install`
* `source tests/environment`

You can then run the tests using:

* `vendor/bin/codecept run`

## Build Status

[![Build Status](https://img.shields.io/travis/mysociety/theyworkforyou.svg)](https://travis-ci.org/mysociety/theyworkforyou)

[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/mysociety/theyworkforyou.svg)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)

[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/mysociety/theyworkforyou.svg)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)

[![mySociety Installability](http://img.shields.io/badge/installability-bronze-8c7853.svg)](http://mysociety.github.io/installation-standards.html)
