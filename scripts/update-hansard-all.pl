#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../../perllib";

use mySociety::Config;
mySociety::Config::set_file('../conf/general');

use DBI; 

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASSWORD'), { RaiseError => 1, PrintError => 0 });

my $sth = $dbh->prepare("update hansard set htime=? where gid = ?");
for my $file (sort </home/twfy-live/hansard-updates/h*>) {
        open FP, $file;
        while (<FP>) {
                next if /^--/;
                my ($gid, $time) = split /\t/;
                next unless $time;
                $sth->execute($time, "uk.org.publicwhip/debate/$gid");
        }
        close FP;
}

$dbh->disconnect();

