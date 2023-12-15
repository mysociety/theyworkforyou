#!/usr/bin/env perl

use v5.14;
use warnings;
use utf8;

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../commonlib/perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $verbose = 0;
for( @ARGV ){
    if( $_ eq "--verbose" ){
        $verbose = 1;
        last;
  }
}

use DBI;
use JSON::XS;
use LWP::Simple;

use vars qw($motion_count $policy_count $align_count @policyids);

require 'policyids.pl';
my $json = JSON::XS->new->latin1;

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0, , mysql_enable_utf8 => 1 });

my $policycheck = $dbh->prepare("SELECT policy_id from policies where policy_id = ?");
my $policyadd = $dbh->prepare("INSERT INTO policies (policy_id, title, description) VALUES (?, ?, ?)");

my $motioncheck = $dbh->prepare("SELECT direction, policy_vote FROM policydivisions WHERE division_id = ? AND policy_id = ?");
my $motionadd = $dbh->prepare("INSERT INTO policydivisions (division_id, policy_id, direction, policy_vote) VALUES (?, ?, ?, ?)");
my $motionupdate = $dbh->prepare("UPDATE policydivisions SET direction = ?, policy_vote = ? WHERE division_id = ? AND policy_id = ?");

my $personinfo_set = $dbh->prepare('INSERT INTO personinfo (person_id, data_key, data_value) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE data_value=?');
my $personinfo_check = $dbh->prepare("SELECT data_value from personinfo where data_key = ? and person_id = ?");
my $strong_for_policy_check = $dbh->prepare("SELECT count(*) as strong_votes FROM persondivisionvotes JOIN policydivisions USING (division_id) WHERE policy_id = ? AND person_id = ? AND policy_vote LIKE '%3'");

$motion_count = $policy_count = $align_count = 0;

foreach my $dreamid ( @policyids ) {
    my $policy_url = mySociety::Config::get('TWFY_VOTES_URL') . '/twfy-compatible/popolo/' . $dreamid . '.json';
    my $policy_json = get($policy_url);
    if (!$policy_json) {
        warn "no json file for policy $dreamid at $policy_url";
        next;
    }
    my $policy = $json->decode($policy_json);

    my $curr_policy = $dbh->selectrow_hashref($policycheck, {}, $dreamid);
    # we don't update the policy title or text as we use slightly different
    # descriptions on TWFY in some cases
    if ( !$curr_policy ) {
        $policyadd->execute($dreamid, $policy->{title} || '', $policy->{text});
    }

    $policy_count++;

    say "processing motions for $dreamid" if $verbose;
    process_motions($policy->{aspects}, $dreamid);
    say "processing alignments for $dreamid" if $verbose;
    process_alignments($policy->{alignments}, $dreamid);
}

print "parsed $policy_count policies, $motion_count divisions, and $align_count alignments from JSON\n";

sub process_motions {
    my ($aspects, $dreamid) = @_;
    # Set AutoCommit off
    $dbh->{AutoCommit} = 0;
    for my $motion (@$aspects) {
        $motion_count++;
        say $motion_count if $verbose && $motion_count % 10 == 0;
        my ($motion_num) = $motion->{motion}->{id} =~ /pw-\d+-\d+-\d+-(\d+)/;
        my ($house) = $motion->{motion}->{organization_id} =~ /uk\.parliament\.(\w+)/;
        my $motion_id = $motion->{motion}->{id};

        my $curr_motion;
        $curr_motion = $dbh->selectrow_hashref($motioncheck, {}, $motion_id, $dreamid);
        if ($curr_motion) {
            $curr_motion->{direction} ||= '';
        }

        if ( !defined $curr_motion ) {
            my $r = $motionadd->execute($motion_id, $dreamid, $motion->{direction}, $motion->{motion}->{policy_vote});
            unless ( $r > 0 ) {
                warn "problem creating policydivision for $motion_id / $dreamid, skipping motions\n";
                next;
            }
        } elsif ( $motion->{direction} ne $curr_motion->{direction} ||
                  $motion->{motion}->{policy_vote} ne $curr_motion->{policy_vote}
        ) {
            my $r = $motionupdate->execute($motion->{direction}, $motion->{motion}->{policy_vote}, $motion_id, $dreamid);
            unless ( $r > 0 ) {
                warn "problem updating policydivision $motion_id / $dreamid from $curr_motion->{direction} to $motion->{direction}\n";
            }
        }

        for my $vote ( @{ $motion->{motion}->{ vote_events }->[0]->{votes} } ) {
            my $mp_id_num;
            $mp_id_num = $vote->{id};
            $mp_id_num =~ s:uk.org.publicwhip/person/::;
            next unless $mp_id_num;
            if ( $mp_id_num !~ /^[1-9]\d+$/ ) {
                print "$mp_id_num doesn't look like a valid person id - skipping vote for $motion_id - " . $dreamid . "\n";
                next;
            }

            # if it's a strong vote, i.e. yes3 or no3, then set mp has strong_vote attribute
            my $pw_id = "public_whip_dreammp" . $dreamid . "_has_strong_vote";
            if ( $motion->{motion}->{policy_vote} =~ /3/ ) {
                $personinfo_set->execute($mp_id_num, $pw_id, 1, 1);
            }

            # if the motion has been unset from strong -> weak then check if we need to unset
            # the MP has strong vote attribute
            if ( $curr_motion && $curr_motion->{policy_vote} =~ /3/ && $motion->{motion}->{policy_vote} !~ /3/ ) {
                $personinfo_check->execute( $pw_id, $mp_id_num );
                if ( $personinfo_check->rows() > 0 ) {
                    $strong_for_policy_check->execute( $dreamid, $mp_id_num );
                    my $row = $strong_for_policy_check->fetchrow_hashref();
                    if ( $row->{strong_votes} == 0 ) {
                        $personinfo_set->execute($mp_id_num, $pw_id, 0, 0);
                    }
                }
            }
        }
    }
    $dbh->commit();
    # Set AutoCommit on
    $dbh->{AutoCommit} = 1;
}

sub process_alignments {
    my ($alignments, $dreamid) = @_;
    foreach (@$alignments) {
        $align_count++;
        say $align_count if $verbose && $align_count % 100 == 0;

        my $person_id = $_->{person_id};
        $person_id =~ s:uk.org.publicwhip/person/::;

        foreach my $term (
            [ distance => 'person_distance_from_policy' ],
            [ both_voted => 'count_present' ],
            [ absent => 'count_absent' ],
        ) {
            my $pw_id = "public_whip_dreammp${dreamid}_$term->[0]";
            my $val = $_->{$term->[1]};
            $personinfo_set->execute($person_id, $pw_id, $val, $val);
        }
    }
}
