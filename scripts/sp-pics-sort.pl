#!/usr/bin/perl -w -I../../perllib

use strict;
use DBI;
use mySociety::Config;
mySociety::Config::set_file('../conf/general');

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASS'), { RaiseError => 1, PrintError => 0 });

my $q = $dbh->selectall_hashref('select member_id,person_id from member where house=4', 'member_id');

foreach my $mid (sort keys %$q) {
	my $pid = $q->{$mid}{person_id};
	my $s_in = "/home/twfy-live/parldata/cmpages/sp/msp-images/49x59/$mid.png";
	my $l_in = "/home/twfy-live/parldata/cmpages/sp/msp-images/98x118/$mid.png";
	my $s_out = "/home/matthew/twfy/twfy/www/docs/images/mps/$pid.png";
	my $l_out = "/home/matthew/twfy/twfy/www/docs/images/mpsL/$pid.png";
	next if -f $s_out || -f $l_out;
	`cp $s_in $s_out` if -f $s_in;
	`cp $l_in $l_out` if -f $l_in;
}

