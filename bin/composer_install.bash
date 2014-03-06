#!/bin/bash
set -e
DIR=`dirname $BASH_SOURCE`/..
cd $DIR
php composer.phar self-update
php composer.phar install --no-dev --optimize-autoloader
