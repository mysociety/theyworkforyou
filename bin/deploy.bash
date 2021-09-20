#!/bin/bash
set -e

DIR=$(cd "$(dirname "$BASH_SOURCE")" && pwd -P)/..
cd $DIR

if [ "$DEV_MODE" = true ]; then
php composer.phar install --optimize-autoloader
else
php composer.phar install --no-dev --optimize-autoloader
fi
# Make sure that the compass gem is installed locally:
bundle install --deployment --binstubs "vendor/bundle-bin"
export PATH="$DIR/vendor/bundle-bin:$PATH"

# Now use compass to compile the SCSS:
cd "$DIR/www/docs/style"
bundle exec compass compile
