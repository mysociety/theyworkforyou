Hello everyone,

This is the source code that powers the TheyWorkForYou.com website. It's
mainly written in PHP, although there are also some Perl scripts for database
loading and indexing. The backend parser code is separate and available
[on GitHub](https://github.com/mysociety/parlparse). You can read more about this
on [TheyWorkForYou's parser info page](http://parser.theyworkforyou.com/parser.html)

The TheyWorkForYou source code in this distribution is released under a BSD
style license. Roughly, this means you are free to copy, use, modify and
redistribute the code or binaries made from the code. Commercial or non-
commercial use is allowed. However, we disclaim warranty, and expect you not to
use our name without our permission. See the file LICENSE.md for exact legal
information.

## What is TheyWorkForYou anyway?

Everything MPs say in the UK's House of Commons is recorded in a document
called Hansard; TheyWorkForYou helps make sense of this vital democratic
resource. It also includes things from the House of Lords, the Scottish
Parliament, and the Northern Ireland Assembly.

## How on earth do I use this code?

See INSTALL.md for installation questions.

If you have questions, the best place to ask is the mySociety TheyWorkForYou
email list at
https://groups.google.com/a/mysociety.org/forum/#!forum/theyworkforyou

### Developing with Vagrant

You will need the latest versions of VirtualBox and Vagrant, then:

* Run `vagrant up`.
* Go make a cup of tea. It may take a while whilst Vagrant and Puppet do their thing.
* Point your web browser at `http://10.11.12.13` and marvel at modern technology.

#### Compiling Static Assets

If you're working on a page which uses the redesign, you will need to compile
static assets after changes:

* `vagrant ssh`
* `cd /vagrant/theyworkforyou/www/docs/style`
* `compass compile` for a one-off compilation or `compass watch` to recompile on changes

## Testing

TheyWorkForYou includes a test suite, using PHPunit. To run tests, ensure that
the environment variables `TWFY_TEST_DB_HOST`, `TWFY_TEST_DB_NAME`,
`TWFY_TEST_DB_USER` and `TWFY_TEST_DB_PASS` are set and contain relevant
information for your testing database. The database will be stripped down and
rebuilt during testing, so make sure it's not an important copy.

You may find that in some versions of PHPUnit errors are thrown regarding code
coverage reports. If this is the case, the version installed by [Composer](http://getcomposer.org/)
and located at `/vendor/bin/phpunit` should run correctly.

## Database Migrations

This application uses [phinx](https://phinx.org) for database migrations.

- Create database migration
```
vendor/bin/phinx create <MIGRATION_NAME> 
```

- Apply database migration (development environment)
```
vendor/bin/phinx migrate -e development
```

- Roll back database migration
```
vendor/bin/phinx rollback -e development
```

For more details, see [phinx docs](https://book.cakephp.org/3.0/en/phinx/commands.html).

## Build Status

[![Build Status](https://img.shields.io/travis/mysociety/theyworkforyou.svg)](https://travis-ci.org/mysociety/theyworkforyou)

[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/mysociety/theyworkforyou.svg)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)

[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/mysociety/theyworkforyou.svg)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)

[![mySociety Installability](http://img.shields.io/badge/installability-bronze-8c7853.svg)](http://mysociety.github.io/installation-standards.html)

## Acknowledgements

Thanks to [Browserstack](https://www.browserstack.com/) who let us use their
web-based cross-browser testing tools for this project.
