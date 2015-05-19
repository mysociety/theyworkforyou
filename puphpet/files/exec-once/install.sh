#!/usr/bin/env bash

cd /data/twfy

composer install
git submodule update --init

cp /data/twfy/conf/general-example /data/twfy/conf/general

sed -i 's/define ("OPTION_TWFY_DB_USER", "username");/define ("OPTION_TWFY_DB_USER", "twfy");/g' /data/twfy/conf/general
sed -i 's/define ("OPTION_TWFY_DB_PASS", "password");/define ("OPTION_TWFY_DB_PASS", "twfy");/g' /data/twfy/conf/general

sed -i 's/define ("BASEDIR","\/home\/user\/theyworkforyou\/docs");/define ("BASEDIR","\/data\/twfy\/www\/docs");/g' /data/twfy/conf/general

sed -i 's/define ("RAWDATA", "\/home\/twfy\/pwdata\/");/define ("RAWDATA", "\/data\/twfy\/uml-tests\/parldata\/");/g' /data/twfy/conf/general
sed -i "s/define ('PWMEMBERS', '\/home\/twfy\/parlparse\/members\/');/define ('PWMEMBERS', '\/data\/parlparse\/members\/');/g" /data/twfy/conf/general
sed -i 's/define ("XAPIANDB", "");/define ("XAPIANDB", "\/data\/searchdb\/");/g' /data/twfy/conf/general

sed -i 's/define ("DOMAIN", "www.example.org");/define ("DOMAIN", "twfy.mysociety");/g' /data/twfy/conf/general
sed -i 's/define ("COOKIEDOMAIN", "www.example.org");/define ("COOKIEDOMAIN", "twfy.mysociety");/g' /data/twfy/conf/general

if ! dpkg -l php5-xapian 2>/dev/null | grep -q '^.i'; then
    echo $DONE_MSG
    # Install php5-xapian from source
    # From http://trac.xapian.org/wiki/FAQ/PHP%20Bindings%20Package
    echo "Installing php5-xapian from source..."
    cd /tmp
    echo -n "  Build dependencies for xapian-bindings... "
    apt-get -qq build-dep xapian-bindings >/dev/null
    echo $DONE_MSG
    echo "  PHP dev headers and devscripts... "
    apt-get -qq install php5-dev php5-cli devscripts >/dev/null
    echo $DONE_MSG
    echo -n "  xapian-bindings source... "
    apt-get -qq source xapian-bindings >/dev/null
    echo $DONE_MSG
    cd xapian-bindings-1.2.*
    rm -f debian/control debian/*-stamp
    echo -n "  debian/rules maint with PHP_VERSIONS set... "
    env PHP_VERSIONS=5 debian/rules maint >/dev/null
    echo $DONE_MSG
    sed -i 's!include_path=php5$!include_path=$(srcdir)/php5!' php/Makefile.in
    echo auto-commit >> debian/source/options
    echo -n "  debuild... "
    # disable tests as they fail on PHP 5.4
    env DEB_BUILD_OPTIONS=nocheck debuild -e PHP_VERSIONS=5 -us -uc
    echo $DONE_MSG
    cd ..
    echo -n "  installing package... "
    dpkg -i php5-xapian_*.deb >/dev/null
    rm -rf *xapian*
fi

mkdir /data/parlparse
git clone https://github.com/mysociety/parlparse.git /data/parlparse

mkdir /data/searchdb
mkdir /data/parldata

/data/twfy/scripts/xml2db.pl --wrans --westminhall --debates --wms --lordsdebates --ni --scotland --scotwrans --scotqs --standing --all
/data/twfy/scripts/load-people
/data/twfy/scripts/mpinfoin.pl
cd /data/twfy/scripts
python future-fetch.py
perl /data/twfy/search/index.pl all

cd /data/twfy/www/docs/style
compass compile
