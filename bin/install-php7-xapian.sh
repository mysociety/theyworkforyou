#!/bin/sh
#
# Install php7-xapian from source
#
# Based on http://trac.xapian.org/wiki/FAQ/PHP%20Bindings%20Package, but
# as we're using Xapian from stretch-backports and there's no corresponding
# source package in the repos we manage that and its dependencies directly.
#

# Root URL for the xapian-bindings source for version 1.4.9 
# matching the binaries in stretch-backports
SOURCE_URL=https://snapshot.debian.org/archive/debian/20190625T042133Z/pool/main/x/xapian-bindings/

# version of PHP we're building for
v=7.0

echo "Checking for php7-xapian package... "
if ! dpkg -l php7-xapian 2>/dev/null | grep -q '^.i'; then
    echo "Installing php7-xapian from source..."
    cd /tmp

    echo "  Xapian dev headers from backports..."
    apt-get -qq -t stretch-backports install libxapian-dev >/dev/null

    echo "  build-essential and devscripts... "
    apt-get -qq install build-essential devscripts >/dev/null

    echo "  PHP dev headers... "
    apt-get -qq install php$v-dev php$v-cli >/dev/null

    echo "  Getting xapian-bindings source from snapshot.debian.org... "
    wget ${SOURCE_URL}/xapian-bindings_1.4.11.orig.tar.xz
    wget ${SOURCE_URL}/xapian-bindings_1.4.11-2.debian.tar.xz
    wget ${SOURCE_URL}/xapian-bindings_1.4.11-2.dsc
    tar xJf xapian-bindings_1.4.11.orig.tar.xz
    tar xJf xapian-bindings_1.4.11-2.debian.tar.xz -C xapian-bindings-1.4.11
    cd xapian-bindings-1.4.11
    echo php7 > debian/bindings-to-package

    echo "  debian/rules maint with PHP7_VERSIONS set... "
    debian/rules maint PHP7_VERSIONS=$v >/dev/null

    echo "  debuild... "
    env DEB_BUILD_OPTIONS=nocheck debuild -e PHP7_VERSIONS=$v -us -uc >/dev/null
    cd ..

    echo "  installing package... "
    dpkg -i php7-xapian_*.deb >/dev/null

    echo "  enabling xapian extension for cgi and cli..."
    /usr/sbin/phpenmod xapian

    rm -rf *xapian*
fi
