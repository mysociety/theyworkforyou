#!/bin/bash

set -e

if [ "${BASH_VERSINFO:-0}" -lt 4 ] ; then
    echo "Bash version 4 or greater is required."
    exit 1
fi

echo "Starting complete index task at $(date)."

# Do it in bits, as Perl and MySQL sometimes can't cope with it all in one go.
for YEAR in {1919..2020} ; do

    # Bash 4+ will do the right thing with the leading zero.
    for MONTH in {01..12} ; do
        echo "Indexing ${YEAR}-${MONTH} at $(date)..."
        ./index.pl daterange ${YEAR}-${MONTH}-01 ${YEAR}-${MONTH}-31
    done

done

echo "Checking index at $(date)..."
./index.pl check

echo "Finished at $(date)."
