#!/bin/sh
set -e

echo "Removing old tempbreaktest..."
rm -fr tempbreaktest
echo "Indexing..."
./testindex.pl tempbreaktest
echo "Testing..."
./search.pl tempbreaktest thin
echo "If it is all 100% and 0w then it is broken"

# Another test, needs xapian-examples
# delve tempbreaktest/ -v -1 -t thin

