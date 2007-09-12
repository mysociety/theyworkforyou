#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use config; # see config.pm.incvs

use DBI; 

my $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });

my $sth = $dbh->prepare("update hansard set htime=? where gid = ?");
while (<>) {
        next if /^--/;
        my ($gid, $time) = split /\t/;
        $sth->execute($time, "uk.org.publicwhip/debate/$gid");
}

$dbh->disconnect();

