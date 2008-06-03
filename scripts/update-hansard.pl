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

my $sthC = $dbh->prepare("select atime from video_timestamps where user_id=-1 and gid=?");
my $sthI = $dbh->prepare("insert into video_timestamps (user_id, atime, gid) values (-1, ?, ?) on duplicate key update atime=VALUES(atime)");
my $sthH = $dbh->prepare('update hansard set video_status = video_status | 2 where gid = ?');
while (<>) {
        next if /^--/;
        my ($gid, $time) = split /\t/;
        next unless $time;
        my $atime = $dbh->selectrow_array($sthC, {}, "uk.org.publicwhip/debate/$gid");
        if (!$atime || $atime ne $time) {
                $sthI->execute($time, "uk.org.publicwhip/debate/$gid");
                $sthH->execute("uk.org.publicwhip/debate/$gid");
        }
}

$dbh->disconnect();

