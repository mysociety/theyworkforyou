#!/bin/sh

echo "Checking for php7-xapian package... "
if ! dpkg -l php7-xapian 2>/dev/null | grep -q '^.i'; then
    # Install php5-xapian from source
    # From http://trac.xapian.org/wiki/FAQ/PHP%20Bindings%20Package
    echo "Installing php7-xapian from source..."
    cd /tmp
    echo "  Build dependencies for xapian-bindings... "
    apt-get -qq build-dep xapian-bindings >/dev/null

    v=7.0
    echo "  PHP dev headers and devscripts... "
    apt-get -qq install devscripts php$v-dev php$v-cli >/dev/null
    echo "  xapian-bindings source... "
    apt-get -qq source xapian-bindings=1.4.3-1 >/dev/null
    cd xapian-bindings-1.4.3

    echo "Get 1.4.5-1 debian bindings"
    rm -fr debian
    git clone --quiet https://salsa.debian.org/olly/xapian-bindings.git debian
    cd debian
    git checkout --quiet debian/1.4.5-1
    git checkout --quiet debian/1.4.3-1 changelog
    cd ..

    echo php7 > debian/bindings-to-package
    echo "  debian/rules maint with PHP7_VERSIONS set... "
    debian/rules maint PHP7_VERSIONS=$v >/dev/null
    echo "  debuild... "
    env DEB_BUILD_OPTIONS=nocheck debuild -e PHP7_VERSIONS=$v -us -uc >/dev/null
    cd ..
    echo "  installing package... "
    dpkg -i php7-xapian_*.deb >/dev/null
    rm -rf *xapian*
fi
