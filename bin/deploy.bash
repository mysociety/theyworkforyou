#!/bin/bash
set -e

DIR=`dirname $BASH_SOURCE`/..
cd $DIR
php composer.phar install --no-dev --optimize-autoloader

# Make sure that the compass gem is installed locally:
export GEM_HOME="$(readlink -f ../gems)"
mkdir -p "$GEM_HOME"
export PATH="$GEM_HOME/bin:$PATH"

gem install --no-rdoc --no-ri compass

# Now use compass to compile the SCSS:
DIR=`dirname $BASH_SOURCE`/../www/docs/style
cd $DIR
compass compile
