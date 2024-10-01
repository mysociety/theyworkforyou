# TheyWorkForYou 

Hello everyone,

This is the source code that powers the TheyWorkForYou.com website. It's
mainly written in PHP, although there are also some Perl scripts for database
loading and indexing. The backend parser code is separate and available
[on GitHub](https://github.com/mysociety/parlparse). You can read more about this
on [TheyWorkForYou's parser info page](https://parser.theyworkforyou.com/parser.html)

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

See [INSTALL.md](INSTALL.md) for installation questions.

If you have questions, the best place to ask is the mySociety TheyWorkForYou
email list at
https://groups.google.com/a/mysociety.org/forum/#!forum/theyworkforyou

### Developing with Docker

You will need [a recent version of Docker](https://www.docker.com/products/docker-desktop).

Running `docker compose up [ -d ]` will start the environment. The first time you run this it may
take a few minutes as various images are downloaded and the application image built locally.

Once it's done, you should be able to view the front page at `http://localhost:8000`. However
the default build contains no data, so take a look at [INSTALL.md](INSTALL.md) for information
about downloading and importing Parlparse data (members, debates, votes, etc).

You can stop the environment by running `docker compose down`. Adding a `-v` will remove any
Docker volumes that may be in use, including all their data.

[DOCKER.md](DOCKER.md) has some more detailed notes on the development environment, together
with some useful commands and more detailed Docker-specific setup notes.

To use xdebug in VS Code while using WSL, you'll need to set an environmental variable of the WSL_IP within the subsystem of the IP address of the subsystem.
 
# Developing with codespaces

[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/mysociety/theyworkforyou?devcontainer_path=.devcontainer%2Fauto-quick-setup%2Fdevcontainer.json)

Start a new codespace on Github by selecting the Code dropdown (top right), and starting a new codespace (or use the [GitHub CLI](https://github.com/cli/cli)).

You can also use the badge above to use a prebuild with a basic amount of data.

This will setup the Docker container and environment. Once finished, the link to the site should be avaliable in the ports tab of the terminal panel. 

To populate with a minimal amount of data, run `scripts/quick-populate` (about 1 hour).

#### Compiling Static Assets

If you're working on a page which uses the redesign, you will need to compile
static assets after changes:

* `script/watch-css`

or

* `cd www/docs/style`
* `bundle exec compass compile` for a one-off compilation or `bundle exec compass watch` to recompile on changes

## Code formatting

`script/lint` will run php-cs-fixer for php files.

## Testing

TheyWorkForYou includes a test suite, using PHPunit. To run tests, ensure that
the environment variables `TWFY_TEST_DB_HOST`, `TWFY_TEST_DB_NAME`,
`TWFY_TEST_DB_USER` and `TWFY_TEST_DB_PASS` are set and contain relevant
information for your testing database. The database will be stripped down and
rebuilt during testing, so make sure it's not an important copy.

You may find that in some versions of PHPUnit errors are thrown regarding code
coverage reports. If this is the case, the version installed by [Composer](https://getcomposer.org/)
and located at `/vendor/bin/phpunit` should run correctly.

## Build Status

[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/mysociety/theyworkforyou.svg)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)

[![Scrutinizer Quality Score](https://img.shields.io/scrutinizer/g/mysociety/theyworkforyou.svg)](https://scrutinizer-ci.com/g/mysociety/theyworkforyou/)

[![mySociety Installability](https://img.shields.io/badge/installability-bronze-8c7853.svg)](https://pages.mysociety.org/installation-standards.html)

## Acknowledgements

Thanks to [Browserstack](https://www.browserstack.com/) who let us use their
web-based cross-browser testing tools for this project.
