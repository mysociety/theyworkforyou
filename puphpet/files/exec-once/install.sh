#!/usr/bin/env bash

gem install zurb-foundation compass

cd /data/twfy

composer install
git submodule update --init

cp /data/twfy/conf/general-example /data/twfy/conf/general

sed -i 's/define ("OPTION_TWFY_DB_USER", "username");/define ("OPTION_TWFY_DB_USER", "twfy");/g' /data/twfy/conf/general
sed -i 's/define ("OPTION_TWFY_DB_PASS", "password");/define ("OPTION_TWFY_DB_PASS", "twfy");/g' /data/twfy/conf/general

sed -i 's/define ("BASEDIR","\/home\/user\/theyworkforyou\/docs");/define ("BASEDIR","\/data\/twfy\/www\/docs");/g' /data/twfy/conf/general

sed -i 's/define ("RAWDATA", "\/home\/twfy\/pwdata\/");/define ("RAWDATA", "\/data\/twfy\/uml-tests\/parldata\/");/g' /data/twfy/conf/general
sed -i "s/define ('PWMEMBERS', '\/home\/twfy\/parlparse\/members\/');/define ('PWMEMBERS', '\/data\/parlparse\/members\/');/g" /data/twfy/conf/general

sed -i 's/define ("DOMAIN", "www.example.org");/define ("DOMAIN", "www.theyworkforyou.dev");/g' /data/twfy/conf/general
sed -i 's/define ("COOKIEDOMAIN", "www.example.org");/define ("COOKIEDOMAIN", "www.theyworkforyou.dev");/g' /data/twfy/conf/general

mkdir /data/parlparse
git clone https://github.com/mysociety/parlparse.git /data/parlparse

/data/twfy/scripts/xml2db.pl --members --wrans --westminhall --debates --wms --lordsdebates --ni --scotland --scotwrans --scotqs --standing --all
/data/twfy/scripts/mpinfoin.pl

cd /data/twfy/www/docs/style
compass compile
