#! /usr/bin/perl -w

use strict;
use utf8;

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../commonlib/perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $parldata = mySociety::Config::get('RAWDATA');

my $verbose = 0;
for( @ARGV ){
    if( $_ eq "--verbose" ){
        $verbose = 1;
        last;
  }
}

use DBI;
use File::Slurp::Unicode;
use JSON::XS;

use vars qw($motion_count $policy_count $vote_count %motions_seen @policyids);

require 'policyids.pl';
my $json = JSON::XS->new->latin1;

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0, , mysql_enable_utf8 => 1 });

my $policycheck = $dbh->prepare("SELECT policy_id from policies where policy_id = ?");
my $policyadd = $dbh->prepare("INSERT INTO policies (policy_id, title, description) VALUES (?, ?, ?)");

my $divisioncheck = $dbh->prepare("SELECT division_title, gid, yes_text, no_text, yes_total, no_total, absent_total, both_total, majority_vote FROM divisions WHERE division_id = ?");
my $divisionadd = $dbh->prepare("INSERT INTO divisions (division_id, house, division_title, yes_text, no_text, division_date, division_number, gid, yes_total, no_total, absent_total, both_total, majority_vote) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
my $divisionupdate = $dbh->prepare("UPDATE divisions SET gid = ?, division_title = ?, yes_text = ?, no_text = ?, yes_total = ?, no_total = ?, absent_total = ?, both_total = ?, majority_vote = ? WHERE division_id = ?");

my $motioncheck = $dbh->prepare("SELECT direction, policy_vote FROM policydivisions WHERE division_id = ? AND policy_id = ?");
my $motionadd = $dbh->prepare("INSERT INTO policydivisions (division_id, policy_id, direction, policy_vote) VALUES (?, ?, ?, ?)");
my $motionupdate = $dbh->prepare("UPDATE policydivisions SET direction = ?, policy_vote = ? WHERE division_id = ? AND policy_id = ?");

my $votecheck = $dbh->prepare("SELECT person_id, vote FROM persondivisionvotes WHERE division_id = ?");
my $voteadd = $dbh->prepare("INSERT INTO persondivisionvotes (person_id, division_id, vote) VALUES (?, ?, ?)");
my $voteupdate= $dbh->prepare("UPDATE persondivisionvotes SET vote = ? WHERE person_id = ? AND division_id = ?");

my $strong_vote_check = $dbh->prepare("SELECT data_value from personinfo where data_key = ? and person_id = ?");
my $strong_for_policy_check = $dbh->prepare("SELECT count(*) as strong_votes FROM persondivisionvotes JOIN policydivisions USING (division_id) WHERE policy_id = ? AND person_id = ? AND policy_vote LIKE '%3'");
my $strong_vote_add = $dbh->prepare("INSERT into personinfo ( data_key, data_value, person_id ) VALUES ( ?, ?, ? )");
my $strong_vote_update = $dbh->prepare("UPDATE personinfo SET data_value = ? WHERE data_key = ? AND person_id = ?");

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

    my $curr_policy = $dbh->selectrow_hashref($policycheck, {}, $dreamid);
    # we don't update the policy title or text as we use slightly different
    # descriptions on TWFY in some cases
    if ( !$curr_policy ) {
        $policyadd->execute($dreamid, $policy->{title} || '', $policy->{text});
    }

    $policy_count++;

    if ($verbose){
        print("processing motions for $dreamid\n");
    }  
    process_motions($policy, $dreamid);
}

# And recently changed ones
my $policy_file = $motionsdir . "recently-changed-divisions.json";
if (-f $policy_file) {
    if ($verbose){
        print("processing recently changed divisions\n");
    }
    my $policy_json = read_file($policy_file);
    my $policy = $json->decode($policy_json);
    process_motions($policy);
}

print "parsed $policy_count policies, $motion_count divisions and $vote_count votes from PW JSON\n";

sub process_motions {
    my ($policy, $dreamid) = @_;
    # Set AutoCommit off
    $dbh->{AutoCommit} = 0;
    for my $motion ( @{ $policy->{aspects} } ) {
        $motion_count++;
        if ($verbose && $motion_count % 10 == 0){
            print("$motion_count\n");
        };
        my ($motion_num) = $motion->{motion}->{id} =~ /pw-\d+-\d+-\d+-(\d+)/;
        my ($house) = $motion->{motion}->{organization_id} =~ /uk\.parliament\.(\w+)/;

        my $sources = $motion->{motion}->{sources};
        my $gid = '';
        foreach my $source (@$sources) {
            if ( defined $source->{gid} ) {
                $gid = $source->{gid};
            }
        }

        my $motion_id = $motion->{motion}->{id};
        my $text = $motion->{motion}->{text};

        my $curr_division = $dbh->selectrow_hashref($divisioncheck, {}, $motion_id);
        if ( $curr_division ) {
            $curr_division->{yes_text} ||= '';
            $curr_division->{no_text} ||= '';
        }

        my $curr_motion;
        if ($dreamid) {
            $curr_motion = $dbh->selectrow_hashref($motioncheck, {}, $motion_id, $dreamid);
            if ($curr_motion) {
                $curr_motion->{direction} ||= '';
            }
        }

        my $yes_text = '';
        my $no_text = '';
        if ( $motion->{motion}->{actions} ) {
            $yes_text = $motion->{motion}->{actions}->{yes};
            $no_text = $motion->{motion}->{actions}->{no};
        }

        my $totals = {
            yes => 0,
            no => 0,
            absent => 0,
            both => 0,
        };
        my $majority_vote = '';

        if ( $motion->{motion}->{vote_events}->[0]->{counts} ) {
            for my $count ( @{ $motion->{motion}->{vote_events}->[0]->{counts} } ) {
                $totals->{$count->{option}} = $count->{value};
            }

            if ($totals->{yes} > $totals->{no}) {
                $majority_vote = 'aye';
            } else {
                $majority_vote = 'no';
            }
        }

        # Ignore tellers in totals
        $totals->{yes} -= grep { $_->{option} =~ /tellaye/ } @{ $motion->{motion}->{ vote_events }->[0]->{votes} };
        $totals->{no} -= grep { $_->{option} =~ /tellno/ } @{ $motion->{motion}->{ vote_events }->[0]->{votes} };

        if ( !defined $curr_division ) {
            my $r = $divisionadd->execute($motion_id, $house, $motion->{motion}->{text}, $yes_text, $no_text, $motion->{motion}->{date}, $motion_num, $gid, $totals->{yes}, $totals->{no}, $totals->{absent}, $totals->{both}, $majority_vote);
            unless ( $r > 0 ) {
                warn "problem creating division $motion_id, skipping motions\n";
                next;
            }
        } elsif ( $curr_division->{division_title} ne $text ||
                  $curr_division->{gid} ne $gid ||
                  $curr_division->{yes_text} ne $yes_text ||
                  $curr_division->{no_text} ne $no_text ||
                  $curr_division->{yes_total} ne $totals->{yes} ||
                  $curr_division->{no_total} ne $totals->{no} ||
                  $curr_division->{absent_total} ne $totals->{absent} ||
                  $curr_division->{both_total} ne $totals->{both} ||
                  $curr_division->{majority_vote} ne $majority_vote
        ) {
            my $r = $divisionupdate->execute($gid, $text, $yes_text, $no_text, $totals->{yes}, $totals->{no}, $totals->{absent}, $totals->{both}, $majority_vote, $motion_id);
            unless ( $r > 0 ) {
                warn "problem updating division $motion_id from $curr_division->{division_title} to $text AND $curr_division->{gid} to $gid\n";
            }
        }

        if ($dreamid) {
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
        }

        my $curr_votes = $dbh->selectall_hashref($votecheck, 'person_id', {}, $motion_id);

        for my $vote ( @{ $motion->{motion}->{ vote_events }->[0]->{votes} } ) {
            my $mp_id_num;
            $mp_id_num = $vote->{id};
            $mp_id_num =~ s:uk.org.publicwhip/person/::;
            next unless $mp_id_num;
            if ( $mp_id_num !~ /^[1-9]\d+$/ ) {
                print "$mp_id_num doesn't look like a valid person id - skipping vote for $motion_id - " . ($dreamid || "") . "\n";
                next;
            }

            # if we've seen this motion before then don't process it, however we want
            # to make sure that the strong vote processing below happens so we still
            # need to look at all the votes, just not update the details of them in
            # the database
            if ( !$motions_seen{$motion_id} ) {
                $vote_count++;

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

            if ($dreamid) {
                # if it's a strong vote, i.e. yes3 or no3, then set mp has strong_vote attribute
                if ( $motion->{motion}->{policy_vote} =~ /3/ ) {
                    my $pw_id = "public_whip_dreammp" . $dreamid . "_has_strong_vote";
                    my $has_strong = $strong_vote_check->execute( $pw_id, $mp_id_num );
                    if ( $strong_vote_check->rows() < 1 ) {
                        $strong_vote_add->execute( $pw_id, 1, $mp_id_num);
                    }
                }

                # if the motion has been unset from strong -> weak then check if we need to unset
                # the MP has strong vote attribute
                if ( $curr_motion && $curr_motion->{policy_vote} =~ /3/ && $motion->{motion}->{policy_vote} !~ /3/ ) {
                    my $pw_id = "public_whip_dreammp" . $dreamid . "_has_strong_vote";
                    my $has_strong = $strong_vote_check->execute( $pw_id, $mp_id_num );
                    if ( $strong_vote_check->rows() > 0 ) {
                        my $has_strong_for_policy = $strong_for_policy_check->execute( $dreamid, $mp_id_num );
                        my $row = $strong_for_policy_check->fetchrow_hashref();
                        if ( $row->{strong_votes} == 0 ) {
                            $strong_vote_update->execute( 0, $pw_id, $mp_id_num);
                        }
                    }
                }
            }
        }

        # some divisions are in more than one policy and we want to take note of
        # this so we can skip processing of them
        if ( !$motions_seen{$motion_id} ) {
            $motions_seen{$motion_id} = 1;
        }

    }
    $dbh->commit();
    # Set AutoCommit on
    $dbh->{AutoCommit} = 1;
}
