#!/bin/bash
set -e
DIR=`dirname $BASH_SOURCE`/../www/docs/style
cd $DIR
compass compile
