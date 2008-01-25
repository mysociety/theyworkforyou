#!/usr/bin/perl -w

use strict;
use config;
use DBI;

my $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });

my $q = $dbh->selectall_hashref('select member_id,person_id from member where house=4', 'member_id');

foreach my $mid (sort keys %$q) {
	my $pid = $q->{$mid}{person_id};
	my $s_in = "/home/fawkes/parldata/cmpages/sp/msp-images/49x59/$mid.png";
	my $l_in = "/home/fawkes/parldata/cmpages/sp/msp-images/98x118/$mid.png";
	my $s_out = "~/staging.theyworkforyou.com/docs/images/mps/$pid.png";
	my $l_out = "~/staging.theyworkforyou.com/docs/images/mpsL/$pid.png";
	`cp $s_in $s_out` if -f $s_in;
	`cp $l_in $l_out` if -f $l_in;
}

