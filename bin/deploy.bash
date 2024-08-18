#!/bin/bash
set -e

DIR=$(cd "$(dirname "$BASH_SOURCE")" && pwd -P)/..
cd $DIR
php composer.phar install --no-dev --optimize-autoloader

# Make sure that the compass gem is installed locally:
bundle install --deployment --binstubs "vendor/bundle-bin"
export PATH="$DIR/vendor/bundle-bin:$PATH"

# setup venv and python packages
python3 -m venv .venv
poetry install

# Now use compass to compile the SCSS:
(cd "$DIR/www/docs/style" && bundle exec compass compile)

commonlib/bin/gettext-makemo --quiet TheyWorkForYou
