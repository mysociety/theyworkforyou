#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;
use utf8;

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../commonlib/perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $parldata = mySociety::Config::get('RAWDATA');
my $lastupdatedir = mySociety::Config::get('INCLUDESPATH') . "../../../xml2db/";

use DBI;
use HTML::Entities;
use File::Find;
use Getopt::Long;
use Data::Dumper;
use File::Slurp::Unicode;
use XML::Twig;

use Uncapitalise;
use JSON::XS;

use vars qw(%membertoperson $motion_count $policy_count $vote_count %motions_seen);
my $json = JSON::XS->new->latin1;

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0 });
my $motioncheck = $dbh->prepare("SELECT division_title, gid FROM policydivisions WHERE division_id = ? AND policy_id = ?");

my $motionadd = $dbh->prepare("INSERT INTO policydivisions (division_id, policy_id, house, division_title, division_date, division_number, gid) VALUES (?, ?, ?, ?, ?, ?, ?)");
my $motionupdate = $dbh->prepare("UPDATE policydivisions SET gid = ?, division_title = ? WHERE division_id = ? AND policy_id = ?");

my $votecheck = $dbh->prepare("SELECT member_id, vote FROM memberdivisionvotes WHERE division_id = ?");
my $voteadd = $dbh->prepare("INSERT INTO memberdivisionvotes (member_id, division_id, vote) VALUES (?, ?, ?)");
my $voteupdate= $dbh->prepare("UPDATE memberdivisionvotes SET vote = ? WHERE member_id = ? AND division_id = ?");

my $motionsdir = $parldata . "scrapedjson/policy-motions/";

$motion_count = $policy_count = $vote_count = 0;

add_mps_and_peers();

foreach my $dreamid (
    363,
    810,
    811,
    826,
    837,
    975,
    984,
    996,
    1027,
    1030,
    1049,
    1050,
    1051,
    1052,
    1053,
    1065,
    1071,
    1074,
    1079,
    1084,
    1087,
    1105,
    1109,
    1110,
    1113,
    1120,
    1124,
    1132,
    1136,
    6667,
    6670,
    6671,
    6672,
    6673,
    6674,
    6676,
    6677,
    6678,
    6679,
    6680,
    6681,
    6682,
    6683,
    6684,
    6685,
    6686,
    6687,
    6688,
    6690,
    6691,
    6692,
    6693,
    6694,
    6695,
    6696,
    6697,
    6698,
    6699,
    6702,
    6703,
    6704,
    6705,
    6706,
    6707,
    6708,
    6709,
    6710,
    6711,
    6715,
    6716,
    6718,
    6719,
    6720,
    6721
) {
    my $policy_file = $motionsdir . $dreamid . ".json";
    if ( ! -f $policy_file ) {
        warn "no json file for policy $dreamid at $policy_file";
        next;
    }
    my $policy_json = read_file($policy_file);
    my $policy = $json->decode($policy_json);
    my $motions = @{ $policy->{aspects} };

    $policy_count++;

    for my $motion ( @{ $policy->{aspects} } ) {
        $motion_count++;
        my ($motion_num, $house);
        ($motion_num = $motion->{motion}->{id}) =~ s/pw-\d+-\d+-\d+-(\d+)/$1/;
        ($house = $motion->{motion}->{organization_id}) =~ s/uk.parliament.(\w+)/$1/;

        my $sources = $motion->{motion}->{sources};
        my $gid = '';
        foreach my $source (@$sources) {
            if ( defined $source->{gid} ) {
                $gid = $source->{gid};
            }
        }

        my $motion_id = $motion->{motion}->{id} . "-$house";

        # some divisions are in more than one policy but the votes don't change so
        # just skip them
        if ( $motions_seen{$motion_id} ) {
            #print "seen $motion_id already, skipping\n";
            next;
        } else {
            $motions_seen{$motion_id} = 1;
        }

        # JSON is UTF-8, the database and TWFY are not
        my $text = Encode::encode( 'iso-8859-1', $motion->{motion}->{text} );
        my $curr_motion = $dbh->selectrow_hashref($motioncheck, {}, $motion_id, $dreamid);

        if ( !defined $curr_motion ) {
            my $r = $motionadd->execute($motion_id, $dreamid, $house, $motion->{motion}->{text}, $motion->{motion}->{date}, $motion_num, $gid);
            unless ( $r > 0 ) {
                warn "problem creating policymotion for $dreamid, skipping motions\n";
                next;
            }
        } elsif ( $curr_motion->{division_title} ne $text || $curr_motion->{gid} ne $gid ) {
            my $r = $motionupdate->execute($gid, $text, $motion_id, $dreamid);
            unless ( $r > 0 ) {
                warn "problem updating division $motion_id from " . $curr_motion->{division_title} . " to $text AND " . $curr_motion->{gid} . " to $gid\n";
            }
        }

        my $curr_votes = $dbh->selectall_hashref($votecheck, 'member_id', {}, $motion_id);

        for my $vote ( @{ $motion->{motion}->{ vote_events }->[0]->{votes} } ) {
            $vote_count++;
            my $mp_id_num;
            $mp_id_num = $vote->{id};
            $mp_id_num = $membertoperson{$mp_id_num};
            if ( !$mp_id_num ) {
                warn "membertoperson lookup failed for " . $vote->{id} . " in policy $dreamid, skipping vote\n";
                next;
            }
            $mp_id_num =~ s:uk.org.publicwhip/person/::;
            next unless $mp_id_num;

            if ( !defined $curr_votes->{$mp_id_num} ) {
                $voteadd->execute($mp_id_num, $motion_id, $vote->{option});
            } elsif ( $curr_votes->{$mp_id_num}->{vote} ne $vote->{option} ) {
                # because we probably want to know if this ever happens
                print "updating $motion_id vote for $mp_id_num from " . $curr_votes->{$mp_id_num}->{vote} . " to " . $vote->{option} . "\n";
                my $r = $voteupdate->execute($vote->{option}, $mp_id_num, $motion_id);
                unless ( $r > 0 ) {
                    warn "problem updating $motion_id vote for $mp_id_num from " . $curr_votes->{$mp_id_num}->{vote} . " to " . $vote->{option} . "\n"
                         . DBI->errstr . "\n";
                 }
            }
        }
    }
}

print "parsed $policy_count policies, $motion_count divisions and $vote_count votes from PW JSON\n";

sub add_mps_and_peers {
    my $pwmembers = mySociety::Config::get('PWMEMBERS');
    my $twig = XML::Twig->new(twig_handlers =>
        { 'person' => \&loadperson },
        output_filter => 'safe' );
    $twig->parsefile($pwmembers . "people.xml");
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
