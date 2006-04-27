#!/bin/sh
set -e

if [ -z "$1" ]
then
	echo "Please specify database as first parameter"
	exit
fi

# Do it in bits, as Perl and MySQL sometimes can't cope with it all in one go.
for YEAR in 1999
do
	for MONTH in 11 12
	do
		./index.pl $1 daterange $YEAR-$MONTH-01 $YEAR-$MONTH-31
	done
done
for YEAR in 2000 2001 2002 2003 2004 2005
do
	for MONTH in 01 02 03 04 05 06 07 08 09 10 11 12
	do
		./index.pl $1 daterange $YEAR-$MONTH-01 $YEAR-$MONTH-31
	done
done
for YEAR in 2006
do
	for MONTH in 01 02 03
	do
		./index.pl $1 daterange $YEAR-$MONTH-01 $YEAR-$MONTH-31
	done
done

./index.pl $1 check
