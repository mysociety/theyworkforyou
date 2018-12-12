#! /usr/bin/perl -w
#
# Loads division data into database.

use strict;
use warnings;

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../commonlib/perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $parldata = mySociety::Config::get('RAWDATA');

use DBI;
use File::Slurp;
use JSON;
use XML::Twig;
use File::Find;
use Getopt::Long;
use TWFY::Utils;

# output_filter 'safe' uses entities &#nnnn; to encode characters, this is
# the easiest/most reliable way to get the encodings correct for content
# output with Twig's ->sprint (content, rather than attributes)
my $outputfilter = 'safe';
#DBI->trace(1);

use vars qw($datefrom $dateto $debates
    $lordsdebates $force $standing
    $scotland
);
my $result = GetOptions(
            "from=s" => \$datefrom,
            "to=s" => \$dateto,
            "debates" => \$debates,
            "lordsdebates" => \$lordsdebates,
            "scotland" => \$scotland,
            "standing" => \$standing,
            );

if ((!$result) || (!$debates && !$lordsdebates && !$standing && !$scotland)) {
    print <<END;

Loads XML files division data from the parldata directory into TheyWorkForYou.

--debates - process Commons Debates
--lordsdebates - process Lords Debates
--scotland  - process Scottish Parliament debates
--standing - process Public Bill Commitees (Standing Committees as were)

--from=YYYY-MM-DD --to=YYYY-MM-DD - process this date range

END
    exit;
}

if ($datefrom || $dateto) {
    $datefrom = "1000-01-01" if !defined $datefrom;
    $dateto = "9999-12-31" if !defined $dateto;
} else {
    $datefrom = "1000-01-01";
    $dateto = "9999-12-31";
}

db_connect();

##########################################################################

use vars qw($curdate $currmajor $currminor);
use vars qw(%membertoperson %personredirect);
my $debatesdir = $parldata . "scrapedxml/debates/";
my $lordsdebatesdir = $parldata . "scrapedxml/lordspages/";
my $scotlanddir = $parldata . 'scrapedxml/sp-new/meeting-of-the-parliament/';
my $standingdir = $parldata . 'scrapedxml/standing/';

my %scotland_vote_store = (
    for => 'aye',
    against => 'no',
    abstentions => 'both',
);

# Do dates in reverse order
sub revsort {
    return reverse sort @_;
}

# Process debates or wrans etc
sub process_type {
    my ($xnames, $xdirs, $xdayfunc) = @_;

    my $process;
    my @xmaxtime;
    my $xmaxfile = "";
    for (my $i=0; $i<@$xdirs; $i++) {
        my $xname = $xnames->[$i];
        my $xdir = $xdirs->[$i];
        $xmaxtime[$i] = 0;

        # Record which dates have files which have been updated
        # (each date can have multiple files, if published Hansard has changed)
        my $xwanted = sub {
            return unless /^$xname(\d{4}-\d\d-\d\d)([a-z]*)\.xml$/
                || /^(\d{4}-\d\d-\d\d)_(\d+)\.xml$/
                || /^$xname\d{4}-\d\d-\d\d_[^_]*_[^_]*_(\d{4}-\d\d-\d\d)([a-z]*)\.xml$/
                || /^$xname\d+_[^_]*_[^_]*_(\d{4}-\d\d-\d\d)([a-z]*)\.xml$/;
            my $xfile = $_;
            my @stat = stat($xdir . $xfile);
            my $date_part = $1;

            if ($xmaxtime[$i] < $stat[9]) {
                $xmaxfile = $xfile;
                $xmaxtime[$i] = $stat[9];
            }

            if ($datefrom le $date_part && $date_part le $dateto) {
                $process->{$date_part} = 1;
            }
        };
        find({ wanted=>$xwanted, preprocess=>\&revsort }, $xdir);
    }

    # Go through dates, and load each one
    my $xname = join(',', @$xnames);
    foreach my $process_date (sort keys %$process) {
        print "db loading $xname $process_date\n";
        &$xdayfunc($process_date);
    }
}

# Load member->person data
my $pwmembers = mySociety::Config::get('PWMEMBERS');
my $j = decode_json(read_file($pwmembers . 'people.json'));
foreach (@{$j->{memberships}}) {
    next if $_->{redirect};
    (my $person_id = $_->{person_id}) =~ s#uk.org.publicwhip/person/##;
    $membertoperson{$_->{id}} = $person_id;
}
foreach (@{$j->{memberships}}) {
    next unless $_->{redirect};
    $membertoperson{$_->{id}} = $membertoperson{$_->{redirect}};
}
foreach (@{$j->{persons}}) {
    next unless $_->{redirect};
    (my $id = $_->{id}) =~ s#uk.org.publicwhip/person/##;
    (my $redirect = $_->{redirect}) =~ s#uk.org.publicwhip/person/##;
    $personredirect{$id} = $redirect;
}

# Process main data
process_type(["debates"], [$debatesdir], \&add_debates_day) if ($debates) ;
process_type(["daylord"], [$lordsdebatesdir], \&add_lordsdebates_day) if ($lordsdebates);
process_type(['sp'], [$scotlanddir], \&add_scotland_day) if $scotland;
process_type(['standing'], [$standingdir], \&add_standing_day) if $standing;

##########################################################################
# Utility

# Parse all the files which match the glob using twig.
sub parsefile_glob {
    my ($twig, $glob) = @_;
    my @files = glob($glob);
    foreach (@files) {
        #print "twigging: $_\n";
        $twig->parsefile($_);
    }
}

##########################################################################
# Database

my ($dbh, $divisionupdate, $voteupdate, $hupdate);

sub db_connect {
    # Connect to database, and prepare queries
    my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
    $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0, , mysql_enable_utf8 => 1 });

    $hupdate = $dbh->prepare("update hansard set htype=14 where gid=?");

    # Divisions
    $divisionupdate = $dbh->prepare("INSERT INTO divisions (division_id, house, division_title, yes_text, no_text, division_date, division_number, gid, yes_total, no_total, absent_total, both_total, majority_vote) VALUES (?, ?, ?, '', '', ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE gid=VALUES(gid), yes_total=VALUES(yes_total), no_total=VALUES(no_total), absent_total=VALUES(absent_total), both_total=VALUES(both_total), majority_vote=VALUES(majority_vote)");
    $voteupdate = $dbh->prepare("INSERT INTO persondivisionvotes (person_id, division_id, vote) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vote=VALUES(vote)");
}

sub person_id {
    my ($item, $member_id_attr) = @_;
    my $person_id = $item->att('person_id') || $membertoperson{$item->att($member_id_attr) || ""} || 'unknown';
    $person_id =~ s/.*\///;
    $person_id = $personredirect{$person_id} || $person_id;
    return $person_id;
}

##########################################################################
# Debates (as a stream of headings and paragraphs)

sub add_debates_day {
    my ($date) = @_;
    my $twig = XML::Twig->new(twig_handlers => {
        'minor-heading' => sub { do_load_subheading($_) },
        'major-heading' => sub { do_load_heading($_) },
        'division' => sub { load_debate_division($_, 1) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    parsefile_glob($twig, $parldata . "scrapedxml/debates/debates" . $curdate. "*.xml");

    undef $twig;
}

# load <division> tags
sub load_debate_division {
    my ($division, $major) = @_;

    my $gid = $division->att('id');
    my $divdate = $division->att('divdate');
    my $divnumber = $division->att('divnumber');
    my $house = $major == 101 ? 'lords' : 'commons';
    my $division_id = "pw-$divdate-$divnumber-$house";

    my $totals = {
        aye => 0,
        no => 0,
        absent => 0, # Always going to be 0 on this import
        both => 0,
    };

    my $divcount = $division->first_child('divisioncount'); # attr ayes noes tellerayes tellernoes
    my ($votes_tag, $vote_tag);
    if ($major == 101) {
        $votes_tag = 'lordlist';
        $vote_tag = 'lord';
    } else {
        $votes_tag = 'mplist';
        $vote_tag = 'mpname';
    }

    my @lists = $division->children($votes_tag);

    # Find any 'both's...
    my %vote_counts_by_pid;
    foreach my $list (@lists) {
        my $side = $list->att('vote');
        die unless $side =~ /^(aye|no|content|not-content)$/;
        my @names = $list->children($vote_tag); # attr ids vote (teller), text is name
        foreach my $vote (@names) {
            my $person_id = person_id($vote, 'id');
            my $vote_direction = $vote->att('vote');
            die unless $vote_direction eq $side;
            $vote_counts_by_pid{$person_id}++;
        }
    }
    foreach (keys %vote_counts_by_pid) {
        $totals->{both}++ if $vote_counts_by_pid{$_} > 1;
    }

    # Okay, now add to database
    foreach my $list (@lists) {
        my $side = $list->att('vote');
        my @names = $list->children($vote_tag); # attr ids vote (teller), text is name
        foreach my $vote (@names) {
            my $person_id = person_id($vote, 'id');
            my $vote_direction = $vote->att('vote');
            my $teller = $vote->att('teller');

            my $stored_vote = $vote_direction =~ /^(aye|content)$/ ? 'aye' : 'no';
            $stored_vote = "tell$stored_vote" if $teller;
            $totals->{$stored_vote}++ if $vote_counts_by_pid{$person_id} == 1;
            $voteupdate->execute($person_id, $division_id, $stored_vote);
        }
    }

    my $majority_vote = $totals->{aye} > $totals->{no} ? 'aye': 'no';
    my $title = division_title($divnumber);
    $divisionupdate->execute($division_id, $house, $title, $divdate, $divnumber, $gid,
        $totals->{aye}, $totals->{no}, $totals->{absent}, $totals->{both}, $majority_vote);
    $hupdate->execute($gid);
}

sub division_title {
    my $divnumber = shift;
    my $heading = join(" &#8212; ", $currmajor || (), $currminor || ());
    return $heading || "Division No. $divnumber";
}

##########################################################################
# Lords Debates (as a stream of headings and paragraphs)

sub add_lordsdebates_day {
    my ($date) = @_;
    my $twig = XML::Twig->new(twig_handlers => {
        'minor-heading' => sub { do_load_subheading($_) },
        'major-heading' => sub { do_load_heading($_) },
        'division' => sub { load_debate_division($_, 101) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    parsefile_glob($twig, $parldata . "scrapedxml/lordspages/daylord" . $curdate. "*.xml");

    undef $twig;
}

##########################################################################
# Scottish Parliament

sub add_scotland_day {
    my ($date) = @_;

    # This script now is hardcoded to only use the new Scottish
    # Parliament data.  This exists for the whole of the
    # parliament, but we should only use it for days after
    # 2011-01-13, since the earlier data from before the
    # parliament website was changed is much higher quality.
    if ($date lt "2011-01-14") {
        return;
    }

    my $twig = XML::Twig->new(twig_handlers => {
        'minor-heading' => sub { do_load_subheading($_) },
        'major-heading' => sub { do_load_heading($_) },
        'division' => sub { load_scotland_division($_) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    parsefile_glob($twig, $parldata . "scrapedxml/sp-new/meeting-of-the-parliament/" . $curdate. "*.xml");

    undef $twig;
}

# load <division> tags
sub load_scotland_division {
    my ($division) = @_;
    my $gid = $division->att('id');
    my $divnumber = $division->att('divnumber') + 1; # Own internal numbering from 0, per day
    my $division_id = "pw-$curdate-$divnumber-scotland";
    my $text = $division->sprint(1);
    my %totals = (for => 0, against => 0, abstentions => 0);
    while ($text =~ m#<mspname id="uk\.org\.publicwhip/member/([^"]*)" vote="([^"]*)">(.*?)\s\(.*?</mspname>#g) {
        my ($member_id, $vote, $name) = ($1, $2, $3);
        my $person_id = $membertoperson{$member_id} || 'unknown';
        $person_id =~ s/.*\///;
        $person_id = $personredirect{$person_id} || $person_id;
        $totals{$vote}++;
        $voteupdate->execute($person_id, $division_id, $scotland_vote_store{$vote});
    }
    while ($text =~ m#<mspname id="uk\.org\.publicwhip/person/([^"]*)" vote="([^"]*)">(.*?)\s\(.*?</mspname>#g) {
        my ($person_id, $vote, $name) = ($1, $2, $3);
        $totals{$vote}++;
        $voteupdate->execute($person_id, $division_id, $scotland_vote_store{$vote});
    }

    my $majority_vote = $totals{for} > $totals{against} ? 'aye': 'no';
    my $title = division_title($divnumber);
    $divisionupdate->execute($division_id, 'scotland', $title, $curdate, $divnumber, $gid,
        $totals{for}, $totals{against}, 0, $totals{abstentions}, $majority_vote);
    $hupdate->execute($gid);
}

##########################################################################
# Standing/Public Bill Committees

sub add_standing_day {
    my ($date) = @_;
    my $twig = XML::Twig->new(twig_handlers => {
        'minor-heading' => sub { do_load_subheading($_) },
        'divisioncount' => sub { load_standing_division($_) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    parsefile_glob($twig, $parldata . "scrapedxml/standing/standing*_*_*_" . $curdate. "*.xml");

    undef $twig;
}

sub load_standing_division {
    my ($division, $id) = @_;
    my $gid = $division->att('id');
    my ($prefix) = $gid =~ m{uk.org.publicwhip/standing/standing(.*?)[a-z]*\.};
    my $divnumber = $division->att('divnumber');
    my $division_id = "pbc-$prefix-$divnumber";
    my $ayes = $division->att('ayes');
    my $noes = $division->att('noes');
    my @names = $division->descendants('mpname');
    foreach (@names) {
        my $person_id = person_id($_, 'memberid');
        my $name = $_->att('membername');
        my $v = $_->att('vote');
        $voteupdate->execute($person_id, $division_id, $v);
    }
    my $majority_vote = $ayes > $noes ? 'aye': 'no';
    my $title = division_title($divnumber);
    $divisionupdate->execute($division_id, 'pbc', $title, $curdate, $divnumber, $gid,
        $ayes, $noes, 0, 0, $majority_vote);
    $hupdate->execute($gid);
}

sub do_load_heading {
    my ($heading) = @_;
    $currmajor = fix_case(strip_string($heading->sprint(1)));
    $currminor = '';
}

sub do_load_subheading {
    my ($heading) = @_;
    $currminor = fix_case(strip_string($heading->sprint(1)));
}
