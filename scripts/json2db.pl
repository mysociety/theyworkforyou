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

use Uncapitalise;
use JSON::XS;

use vars qw($motion_count $policy_count $vote_count %motions_seen @policyids);

require 'policyids.pl';
my $json = JSON::XS->new->latin1;

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0 });

my $policycheck = $dbh->prepare("SELECT policy_id from policies where policy_id = ?");
my $policyadd = $dbh->prepare("INSERT INTO policies (policy_id, title, description) VALUES (?, ?, ?)");
my $policyupdate = $dbh->prepare("UPDATE policies SET title = ?, description = ? WHERE policy_id = ?");

my $motioncheck = $dbh->prepare("SELECT division_title, gid, direction, yes_text, no_text FROM policydivisions WHERE division_id = ? AND policy_id = ?");

my $motionadd = $dbh->prepare("INSERT INTO policydivisions (division_id, policy_id, house, direction, division_title, yes_text, no_text, division_date, division_number, gid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
my $motionupdate = $dbh->prepare("UPDATE policydivisions SET gid = ?, division_title = ?, yes_text = ?, no_text = ?, direction = ? WHERE division_id = ? AND policy_id = ?");

my $votecheck = $dbh->prepare("SELECT person_id, vote FROM persondivisionvotes WHERE division_id = ?");
my $voteadd = $dbh->prepare("INSERT INTO persondivisionvotes (person_id, division_id, vote) VALUES (?, ?, ?)");
my $voteupdate= $dbh->prepare("UPDATE persondivisionvotes SET vote = ? WHERE person_id = ? AND division_id = ?");

my $motionsdir = $parldata . "scrapedjson/policy-motions/";

$motion_count = $policy_count = $vote_count = 0;

foreach my $dreamid ( @policyids ) {
    my $policy_file = $motionsdir . $dreamid . ".json";
    if ( ! -f $policy_file ) {
        warn "no json file for policy $dreamid at $policy_file";
        next;
    }
    my $policy_json = read_file($policy_file);
    my $policy = $json->decode($policy_json);
    my $motions = @{ $policy->{aspects} };


    my $curr_policy = $dbh->selectrow_hashref($policycheck, {}, $dreamid);
    if ( $curr_policy ) {
        $policyupdate->execute($policy->{title} || '', $policy->{text}, $dreamid);
    } else {
        $policyadd->execute($dreamid, $policy->{title} || '', $policy->{text});
    }

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

        my $motion_id = $motion->{motion}->{id};

        if ( !$motion->{direction} ) {
            print "$motion_id in policy $dreamid has no direction, skipping\n";
            next;
        }

        # JSON is UTF-8, the database and TWFY are not
        my $text = Encode::encode( 'iso-8859-1', $motion->{motion}->{text} );
        my $curr_motion = $dbh->selectrow_hashref($motioncheck, {}, $motion_id, $dreamid);

        if ( $curr_motion ) {
            $curr_motion->{direction} ||= '';
            $curr_motion->{yes_text} ||= '';
            $curr_motion->{no_text} ||= '';
        }

        my $yes_text = '';
        my $no_text = '';
        if ( $motion->{motion}->{actions} ) {
            $yes_text = $motion->{motion}->{actions}->{yes};
            $no_text = $motion->{motion}->{actions}->{no};
        }

        if ( !defined $curr_motion ) {
            my $r = $motionadd->execute($motion_id, $dreamid, $house, $motion->{direction}, $motion->{motion}->{text}, $yes_text, $no_text, $motion->{motion}->{date}, $motion_num, $gid);
            unless ( $r > 0 ) {
                warn "problem creating policymotion for $dreamid, skipping motions\n";
                next;
            }
        } elsif ( $curr_motion->{division_title} ne $text ||
                  $curr_motion->{gid} ne $gid ||
                  $curr_motion->{yes_text} ne $yes_text ||
                  $curr_motion->{no_text} ne $no_text ||
                  $motion->{direction} ne $curr_motion->{direction}
        ) {
            my $r = $motionupdate->execute($gid, $text, $yes_text, $no_text, $motion->{direction}, $motion_id, $dreamid);
            unless ( $r > 0 ) {
                warn "problem updating division $motion_id from " . $curr_motion->{division_title} . " to $text AND " . $curr_motion->{gid} . " to $gid\n";
            }
        }

        # some divisions are in more than one policy but the votes don't change so
        # just skip them
        if ( $motions_seen{$motion_id} ) {
            #print "seen $motion_id already, skipping\n";
            next;
        } else {
            $motions_seen{$motion_id} = 1;
        }

        my $curr_votes = $dbh->selectall_hashref($votecheck, 'person_id', {}, $motion_id);

        for my $vote ( @{ $motion->{motion}->{ vote_events }->[0]->{votes} } ) {
            $vote_count++;
            my $mp_id_num;
            $mp_id_num = $vote->{id};
            $mp_id_num =~ s:uk.org.publicwhip/person/::;
            next unless $mp_id_num;
            if ( $mp_id_num !~ /^[1-9]\d+$/ ) {
                print "$mp_id_num doesn't look like a valid person id - skipping vote for $motion_id - $dreamid\n";
                next;
            }

            if ( !defined $curr_votes->{$mp_id_num} ) {
                $voteadd->execute($mp_id_num, $motion_id, $vote->{option});
                $curr_votes->{$mp_id_num} = { vote => $vote->{option}};
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
