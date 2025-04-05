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

my $dev_populate = 0;
for( @ARGV ){
    if( $_ eq "--dev-populate" ){
        $dev_populate = 1;
        last;
  }
}

use DBI;
use JSON::XS;
use LWP::Simple;
use LWP::UserAgent;

use vars qw($motion_count $policy_count $align_count $vote_count %motions_seen);

my $json = JSON::XS->new->latin1;

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0, , mysql_enable_utf8 => 1 });

my $policycheck = $dbh->prepare("SELECT policy_id from policies where policy_id = ?");
my $policyadd = $dbh->prepare("INSERT INTO policies (policy_id, title, description) VALUES (?, ?, ?)");

my $policydivision_check = $dbh->prepare("SELECT direction, policy_vote FROM policydivisions WHERE division_id = ? AND policy_id = ?");
my $policydivision_add = $dbh->prepare("INSERT INTO policydivisions (division_id, policy_id, direction, policy_vote) VALUES (?, ?, ?, ?)");
my $policydivision_update = $dbh->prepare("UPDATE policydivisions SET direction = ?, policy_vote = ? WHERE division_id = ? AND policy_id = ?");

my $personinfo_set = $dbh->prepare('INSERT INTO personinfo (person_id, data_key, data_value) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE data_value=?');
my $personinfo_check = $dbh->prepare("SELECT data_value from personinfo where data_key = ? and person_id = ?");
my $strong_for_policy_check = $dbh->prepare("SELECT count(*) as strong_votes FROM persondivisionvotes JOIN policydivisions USING (division_id) WHERE policy_id = ? AND person_id = ? AND policy_vote LIKE '%3'");

my $divisioncheck = $dbh->prepare("SELECT division_title, gid, yes_text, no_text, yes_total, no_total, absent_total, both_total, majority_vote FROM divisions WHERE division_id = ?");
my $divisionadd = $dbh->prepare("INSERT INTO divisions (division_id, house, division_title, yes_text, no_text, division_date, division_number, gid, yes_total, no_total, absent_total, both_total, majority_vote) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
my $divisionupdate = $dbh->prepare("UPDATE divisions SET gid = ?, division_title = ?, yes_text = ?, no_text = ?, yes_total = ?, no_total = ?, absent_total = ?, both_total = ?, majority_vote = ? WHERE division_id = ?");

my $votecheck = $dbh->prepare("SELECT person_id, vote FROM persondivisionvotes WHERE division_id = ?");
my $voteadd = $dbh->prepare("INSERT INTO persondivisionvotes (person_id, division_id, vote) VALUES (?, ?, ?)");
my $voteupdate= $dbh->prepare("UPDATE persondivisionvotes SET vote = ? WHERE person_id = ? AND division_id = ?");

my $partypolicy_replace = $dbh->prepare("REPLACE INTO partypolicy
    (party, house, policy_id, score, divisions, date_min, date_max)
    VALUES (?, 1, ?, ?, ?, ?, ?)");

$motion_count = $policy_count = $align_count = 0;

my @policyids = fetch_policies();

sub get_url {
    # simplify retrying urls if they fail
    my ($url, $retries) = @_;
    my $ua = LWP::UserAgent->new;
    $ua->timeout(30);  # 30 second timeout
    my $response = $ua->get($url);
    if ($response->is_success) {
        return $response->decoded_content;
    } else {
        if ($retries > 0) {
            sleep(30);
            say "retrying $url" if $verbose;
            return get_url($url, $retries - 1);
        } else {
            return undef;
        }
    }
}

foreach my $dreamid ( @policyids ) {
    say "fetching data for $dreamid" if $verbose;
    my $policy_url = mySociety::Config::get('TWFY_VOTES_URL') . '/twfy-compatible/popolo/' . $dreamid . '.json';
    my $policy_json = get_url($policy_url, 3);
    unless ($policy_json) {
        warn "no json file for policy $dreamid at $policy_url\n";
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

    say "processing policydivisions for $dreamid" if $verbose;
    process_policydivisions($policy->{aspects}, $dreamid);
    say "processing alignments for $dreamid" if $verbose;
    process_alignments($policy->{alignments}, $dreamid);
    if ( $dev_populate ) {
        say "Dev mode - Populating divisions for $dreamid" if $verbose;
        process_motions($policy, $dreamid);
    }
}

print "parsed $policy_count policies, $motion_count divisions, and $align_count alignments from JSON\n";

# ---

sub fetch_policies {
    my $policies_url = mySociety::Config::get('TWFY_VOTES_URL') . '/policies/commons/active/all.json';
    my $policies_json = get_url($policies_url, 3);
    my $policies = $json->decode($policies_json);

    my @ids;
    my $out = {
        sets => {},
        set_descs => {},
        policies => {},
        agreements => {},
    };
    foreach my $policy (@$policies) {
        say "Processing policy $policy->{id} $policy->{name}" if $verbose;
        push @ids, $policy->{id};
        foreach (@{$policy->{groups}}) {
            push @{$out->{sets}{$_->{slug}}}, $policy->{id};
            $out->{set_descs}{$_->{slug}} = $_->{description};
        }
        $out->{policies}{$policy->{id}} = $policy->{context_description};
    }

    # get agreement information to store in the json
    # agreements by definition don't have anything specific for indiv MPs
    # so can be just be simply stored for reference in policy page.
    my $out_agreements = {};

    foreach my $policy (@$policies) {
        my $policy_id = $policy->{"id"};
        foreach my $agreement (@{$policy->{"agreement_links"}}) {
            my $decision = $agreement->{"decision"};
            my $decision_date = $decision->{"date"};
            # adjust decision_ref - split by . and remove the last part and put it back together
            # this is because the decision ref is to a specific line, rather than a linkable section.
            my @parts = split(/\./, $decision->{"decision_ref"});
            pop @parts if @parts > 1;
            my $decision_url_ref = join(".", @parts);
            my $twfy_url = "https://www.theyworkforyou.com/debate/?id=" . $decision_date . $decision_url_ref;
            my $data = {
                "house" => $decision->{"chamber_slug"},
                "date" => $decision_date,
                "gid" => $decision_date . $decision->{"decision_ref"},
                "url" => $twfy_url,
                "division_name" => $decision->{"decision_name"},
                "strength" => $agreement->{"strength"},
                "alignment" => $agreement->{"alignment"},
            };
            push(@{$out_agreements->{$policy_id}}, $data);
        };
    };

    # add the agreements to the output
    $out->{agreements} = $out_agreements;


    $out = $json->encode($out);
    open(my $fp, '>', mySociety::Config::get('RAWDATA') . '/scrapedjson/policies.json');
    $fp->write($out);
    close $fp;

    return @ids;
}

sub process_policydivisions {
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
        $curr_motion = $dbh->selectrow_hashref($policydivision_check, {}, $motion_id, $dreamid);
        if ($curr_motion) {
            $curr_motion->{direction} ||= '';
        }

        if ( !defined $curr_motion ) {
            my $r = $policydivision_add->execute($motion_id, $dreamid, $motion->{direction}, $motion->{motion}->{policy_vote});
            unless ( $r > 0 ) {
                warn "problem creating policydivision for $motion_id / $dreamid, skipping motions\n";
                next;
            }
        } elsif ( $motion->{direction} ne $curr_motion->{direction} ||
                  $motion->{motion}->{policy_vote} ne $curr_motion->{policy_vote}
        ) {
            my $r = $policydivision_update->execute($motion->{direction}, $motion->{motion}->{policy_vote}, $motion_id, $dreamid);
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
    # Set AutoCommit off
    $dbh->{AutoCommit} = 0;
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

        unless ($_->{no_party_comparision}) {
            my $hash = "$person_id-$_->{comparison_party}";
            my $divisions = $_->{count_present} + $_->{count_absent};
            my $start_date = "$_->{start_year}-00-00";
            my $end_date = "$_->{end_year}-00-00";
            $partypolicy_replace->execute(
                $hash, $dreamid, $_->{comparison_distance_from_policy},
                $divisions, $start_date, $end_date);
        }

    }
    $dbh->commit();
    # Set AutoCommit on
    $dbh->{AutoCommit} = 1;
}



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