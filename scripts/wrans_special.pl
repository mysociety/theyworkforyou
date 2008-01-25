#! /usr/bin/perl -w

exit; # Used once, kept for posterity

use strict;
use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use config; # see config.pm.incvs
use DBI; 
db_connect();
my ($dbh, $epcheck, $epupdate, $hcheck, $hupdate, $lastid);
sub db_connect {
        $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });
        $lastid = $dbh->prepare("select last_insert_id()");
}
sub db_disconnect {
        $dbh->disconnect();
}

$hcheck = $dbh->prepare("SELECT epobject_id FROM hansard WHERE gid=?");
my $vupdate = $dbh->prepare("UPDATE anonvotes SET epobject_id=? WHERE epobject_id=?");
my $vuupdate = $dbh->prepare("UPDATE uservotes SET epobject_id=? WHERE epobject_id=?");
my $q = $dbh->prepare("SELECT anonvotes.epobject_id,gid
	FROM anonvotes,hansard WHERE anonvotes.epobject_id=hansard.epobject_id
	AND hdate='2006-05-10' AND major=3 AND gid LIKE '%r0%'");
my $rows = $q->execute();
my $array_ref1 = $q->fetchall_arrayref();
foreach my $row (@$array_ref1) {
	my $eid = $row->[0];
	my $gid = $row->[1];
	(my $newgid = $gid) =~ s/2006-05-10a/2006-05-11c/;
	$hcheck->execute($newgid);
	my $h = $hcheck->fetchrow_arrayref();
	if ($h) {
		my $neweid = $h->[0];
		print "Change $eid to $neweid\n";
		$vupdate->execute($neweid, $eid);
	}
}
$q->finish();
$q = $dbh->prepare("SELECT uservotes.epobject_id,gid
	FROM uservotes,hansard WHERE uservotes.epobject_id=hansard.epobject_id
	AND hdate='2006-05-10' AND major=3 AND gid LIKE '%r0%'");
$rows = $q->execute();
$array_ref1 = $q->fetchall_arrayref();
foreach my $row (@$array_ref1) {
	my $eid = $row->[0];
	my $gid = $row->[1];
	(my $newgid = $gid) =~ s/2006-05-10a/2006-05-11c/;
	$hcheck->execute($newgid);
	my $h = $hcheck->fetchrow_arrayref();
	if ($h) {
		my $neweid = $h->[0];
		print "Change $eid to $neweid\n";
		$vuupdate->execute($neweid, $eid);
	}
}
$q->finish();

sub last_id {
	$lastid->execute();
	my @arr = $lastid->fetchrow_array();
        $lastid->finish();
	return $arr[0];
}
