#!/bin/sh

$DONE_MSG=''

echo -n "Checking for php5-xapian package... "
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
echo $DONE_MSG
