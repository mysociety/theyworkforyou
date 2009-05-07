#! /usr/bin/perl -w

use strict;
use DBI; 
use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../../perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my @words = qw(transport traffic congestion vehicle vehicles
road roads highway highways motorway motorways car cars
rail railway railways tram trams train trains tube station stations
pedestrian pedestrians walking cycling bike bikes bicycle bicycles
aeroplane aeroplanes airplane airplanes airport airports
boat boats ferry ferries port ports
);
my $words = join('|', @words);

# Get any data from the database
my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASS'), { RaiseError => 1, PrintError => 0 });

my %out = ();
my %consts = ();

my $query = "select constituency,member_id,person_id from member 
        where house=1 and (person_id=11068 or person_id in (select person_id from member where house=1 AND curdate() <= left_house)) order by member_id";
my $sth = $dbh->prepare($query);
$sth->execute();
while ( my @row = $sth->fetchrow_array() ) {
    my $const = $row[0];
    my $mp_id = $row[1];
    my $person_id = $row[2];
    my $count = 0;
    print STDERR "Looking at $const ($person_id / $mp_id)...\n";

    push @{$consts{$person_id}}, $const;

    my $tth = $dbh->prepare("select body from epobject,hansard where hansard.epobject_id = epobject.epobject_id and speaker_id=? and (major=1 or major=2)");
    $tth->execute($mp_id);
    while (my @row = $tth->fetchrow_array()) {
            my $body = $row[0];
            $body =~ s/<\/p>/\n\n/g;
            $body =~ s/<\/?p[^>]*>//g;
            my @count = $body =~ m/\b($words)\b/iog;
            $out{$person_id} += scalar @count;
    }
}

foreach (sort keys %out) {
    print "$_\t" . join ('|', @{$consts{$_}}) . "\t" . $out{$_} . "\n";
}
