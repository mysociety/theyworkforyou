#!/bin/bash
set -e

DIR=$(cd "$(dirname "$BASH_SOURCE")" && pwd -P)/..
cd $DIR
php composer.phar install --no-dev --optimize-autoloader

# Make sure that the compass gem is installed locally:
bundle install --deployment --binstubs "vendor/bundle-bin"
export PATH="$DIR/vendor/bundle-bin:$PATH"

# Now use compass to compile the SCSS:
DIR=`dirname $BASH_SOURCE`/../www/docs/style
cd $DIR
compass compile

# Database migrations
vendor/bin/phinx migrate -e development
vendor/bin/phinx migrate -e testing
