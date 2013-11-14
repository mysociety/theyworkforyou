#!/bin/bash
set -e
cd `dirname $BASH_SOURCE`/..
scss -t compressed www/docs/style/global.scss www/docs/style/global.css
