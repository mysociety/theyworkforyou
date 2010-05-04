#! /usr/bin/perl -w
#
# Special version of xml2db.pl that just quickly loads in 2010 general election results.

use strict;
use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../commonlib/perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

use DBI; 
use XML::Twig;
use Data::Dumper;

db_connect();

use vars qw(%membertoperson);

my $pwmembers = mySociety::Config::get('PWMEMBERS');

my $twig = XML::Twig->new(twig_handlers => { 'person' => \&loadperson } );
$twig->parsefile($pwmembers . "people.xml");
undef $twig;

$twig = XML::Twig->new(twig_handlers => { 'member' => \&loadmember } );
$twig->parsefile($pwmembers . "all-members-2010.xml");
undef $twig;

##########################################################################
# Database

my ($dbh, $memberadd, $memberexist, $membercheck);

sub db_connect {
        # Connect to database, and prepare queries
        my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
        $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0 });

        # member (MP) queries
        $memberadd = $dbh->prepare("replace into member (member_id, person_id, house, title, first_name, last_name,
                constituency, party, entered_house, left_house, entered_reason, left_reason) 
                values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $memberexist = $dbh->prepare("select member_id from member where member_id = ?");
        $membercheck = $dbh->prepare("select member_id from member where
                member_id = ? and person_id = ? and house = ? and title = ? and first_name = ? and last_name = ?
                and constituency = ? and party = ? and entered_house = ? and left_house = ?
                and entered_reason = ? and left_reason = ?"); 
}

# Add member of parliament to database
sub db_memberadd {
        my $id = $_[0];
        my @params = @_;
        my $q = $memberexist->execute($id);
        $memberexist->finish();
        die "More than one existing member of same id $id" if $q > 1;

        $params[4] = Encode::encode('iso-8859-1', $params[4]);
        $params[5] = Encode::encode('iso-8859-1', $params[5]);
        $params[6] = Encode::encode('iso-8859-1', $params[6]);
        if ($q == 1) {
                # Member already exists, check they are the same
                $q = $membercheck->execute(@params);
                $membercheck->finish();
                if ($q == 0) {
                        print "Replacing existing member with new data for $id\n";
                        print "This is for your information only, just check it looks OK.\n";
                        print "\n";
                        print Dumper(\@params);
                        $memberadd->execute(@params);
                        $memberadd->finish();
                }
        } else {
                print "Adding new member with identifier $id\n";
                print "This is for your information only, just check it looks OK.\n";
                print "\n";
                print Dumper(\@params);
                $memberadd->execute(@params);
                $memberadd->finish();
        }
}

sub loadmember {
        my ($twig, $member) = @_;

        my $id = $member->att('id');
        my $person_id = $membertoperson{$id};
        $id =~ s:uk.org.publicwhip/member/::;
        $person_id =~ s:uk.org.publicwhip/person/::;

        my $house = 1;
        my $fromdate = $member->att('fromdate');
        my $todate = $member->att('todate');
        my $party = $member->att('party');
        $party = '' if $party eq 'unknown';

        db_memberadd($id, 
                $person_id,
                $house, 
                $member->att('title'),
                $member->att('firstname'),
                $member->att('lastname'),
                $member->att('constituency'),
                $party,
                $fromdate, $todate,
                $member->att('fromwhy'), $member->att('towhy'));

        $twig->purge;
}

sub loadperson {
    my ($twig, $person) = @_;
    my $curperson = $person->att('id');

    for (my $office = $person->first_child('office'); $office;
        $office = $office->next_sibling('office'))
    {
        $membertoperson{$office->att('id')} = $curperson;
    }
}

