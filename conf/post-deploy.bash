#!/bin/bash
set -e
cd `dirname $0`/..
scss -t compressed www/docs/style/global.scss www/docs/style/global.css
