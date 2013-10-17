Hello everyone,

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

Francis Irving  
on behalf of the TheyWorkForYou.com team  
2004-07-10 (updated 2008-04-30)

## What is TheyWorkForYou.com anyway?

Everything MPs say in the UK's House of Commons is recorded in a document called
Hansard. TheyWorkForYou.com helps make sense of this vital democratic resource
and, crucially, allows you to add your own annotations and links to the official
transcripts of Parliament.

## How on earth do I use this code?

See INSTALL.md for installation questions.

If you have questions, the best place to ask is the mySociety developers-public
email list at
https://secure.mysociety.org/admin/lists/mailman/listinfo/developers-public

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

### Build Status

[![Build Status](https://travis-ci.org/mysociety/theyworkforyou.png?branch=master)](https://travis-ci.org/mysociety/theyworkforyou)

[![Coverage Status](https://coveralls.io/repos/mysociety/theyworkforyou/badge.png)](https://coveralls.io/r/mysociety/theyworkforyou)

[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/badges/quality-score.png?s=438f0b4db5d9ce959aa80e4a87ac77af60d92da2)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)