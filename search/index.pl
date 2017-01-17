#!/usr/bin/perl -w
# vim:sw=4:ts=4:et:nowrap

# Indexer of TheyWorkForYou.com using Xapian.

use strict;
use Carp;
use Search::Xapian qw(:standard);
use HTML::Parser;
use HTML::Entities;
use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin/../commonlib/perllib";
use mySociety::Config;
mySociety::Config::set_file('../conf/general');
use DBI;
#use Data::Dumper;
#DBI->trace(2);

# Command line parser
$|=1;
my $dbfile = mySociety::Config::get('XAPIANDB');
my $action = shift;
die "As first parameter, specify:
    'all' to (re)index everything (sometimes memory leaks and doesn't work see indexall.sh), or 
    'lastweek' to (re)index just the last week, or 
    'lastmonth' to (re)index just the last month, or 
    'daterange' to (re)index between two dates, specified as next parameters
    'sincefile' to (re)index updates since a given date specified in unixtime inside a file
    'check' to use xml2db's list of removed GIDs to remove things from Xapian
    'checkfull' to check everything is indexed by fetching entire dbs and comparing
" if !$action or ($action ne "all" and $action ne "lastweek" and $action ne "lastmonth" and $action ne "sincefile" and $action ne "check" and $action ne 'checkfull' and $action ne "daterange");

# If there's a value for XAPIAN_MAX_CHANGESETS in the config file,
# make sure it's in our environment.
my $changesets = mySociety::Config::get('XAPIAN_MAX_CHANGESETS');
$ENV{XAPIAN_MAX_CHANGESETS} = $changesets if $changesets;

# Open MySQL
my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0 });

# Work out when to update from, for "sincefile" case
my $since_date_condition = "";
my $lastupdatedfile;
my $now_start_string;
if ($action eq "sincefile") {
    $lastupdatedfile = "$dbfile/twfy-lastupdated";
    if (-e $lastupdatedfile) {
        # Read unix time from file
        open FH, "<$lastupdatedfile" or die "couldn't open $lastupdatedfile even though it is there";
        $since_date_condition = " where {{modified}} >= from_unixtime('" . (readline FH) . "')";
        close FH;
    }
    # Store time we need to update from next time
    my $sth = $dbh->prepare("select unix_timestamp(now())");
    $sth->execute();
    my @row = $sth->fetchrow_array();
    $now_start_string = $row[0];
} elsif ($action eq "lastweek") {
    $since_date_condition = " where {{date}} > date_sub(curdate(), interval 7 day)";
} elsif ($action eq "lastmonth") {
    $since_date_condition = " where {{date}} > date_sub(curdate(), interval 1 month)";
} elsif ($action eq "daterange") {
    my $datefrom = shift;
    die "As fourth parameter, specify from date in form 2001-06-01" if !$datefrom;
    my $dateto = shift;
    die "As fifth parameter, specify to date in form 2004-10-28" if !$dateto;
    $since_date_condition = " where {{date}} >= '$datefrom' and {{date}} <= '$dateto'";
}

# Section, fed up of indexing things that don't need to be
my $section = shift;
die 'Section must be missing or numeric' if ($section && $section ne 'cronquiet' && $section =~ /\D/);
my $cronquiet;
if ($section && $section eq 'cronquiet') {
    $cronquiet = 'cronquiet';
    $section = '';
} else {
    # Quiet
    $cronquiet=shift;
    die "As last parameter, specify nothing or cronquiet" if ($cronquiet and $cronquiet ne "cronquiet");
}

# Open Xapian database
my $stemmer = new Search::Xapian::Stem('english');
my $db = Search::Xapian::WritableDatabase->new($dbfile, Search::Xapian::DB_CREATE_OR_OPEN);
my $termgenerator = new Search::Xapian::TermGenerator();

$termgenerator->set_flags(Search::Xapian::FLAG_SPELLING);
$termgenerator->set_database($db);
$termgenerator->set_stemmer($stemmer);
# $termgenerator->set_stopper();

my $q_person = $dbh->prepare(
    "SELECT house, title, given_name, family_name, lordofname, party
    FROM member, person_names p
    WHERE member.person_id = ? AND entered_house <= ? and ? <= left_house
        AND member.person_id = p.person_id AND p.type = 'name' AND p.start_date <= ? and ? <= p.end_date
    ORDER BY entered_house");

my %major_to_house = (
    1 => { 1 => 1 },
    2 => { 1 => 1 },
    3 => { 1 => 1, 2 => 1 },
    4 => { 1 => 1, 2 => 1 },
    5 => { 3 => 1 },
    6 => { 1 => 1 },
    7 => { 4 => 1 },
    8 => { 4 => 1 },
    101 => { 2 => 1 },
);

if ($action ne "check" && $action ne 'checkfull') {

    # Batch numbers - each new stuff gets a new batch number
    my $max_indexbatch = $dbh->selectrow_array('select max(indexbatch_id) from indexbatch') || 0;
    my $new_indexbatch = $max_indexbatch + 1;
    
    # Get data for items to update from MySQL 
    my $query = "select epobject.epobject_id, epobject.body, section.body as section_body,
        hdate, htime, gid, major, section_id, subsection_id, colnum,
        unix_timestamp(hansard.created) as created, hpos, person_id
        from epobject join hansard on epobject.epobject_id = hansard.epobject_id
        left join epobject as section on hansard.section_id = section.epobject_id";
    (my $sdc = $since_date_condition) =~ s/{{date}}/hdate/g;
    $sdc =~ s/{{modified}}/hansard.modified/;
    $query .= $sdc;
    $query .= ' and major = ' . $section if ($section);
    $query .= ' ORDER BY hdate,major,hpos';
    my $q = $dbh->prepare($query);
    $q->execute();
    #print "indexing with Xapian, rows from mysql: " . $q->rows();

    my $parser = new HTML::Parser();
    $parser->handler(text => sub {
        my ($p, $text) = @_;
        decode_entities($text); # XXX Data in MySQL is HTML encoded
        $termgenerator->index_text($text);
    });

    croak if !$db;
    my $last_hdate = "";
    my $last_area = "";
    while (my $row = $q->fetchrow_hashref()) {

        my $gid = $$row{'gid'};
        $gid =~ s#uk\.org\.publicwhip/##;

        $gid =~ m#(.*)/#;
        my $area = $1; # wrans or debate or westminhall or wms etc.
        if ($$row{'hdate'} ne $last_hdate || $area ne $last_area) {
            $last_hdate = $$row{'hdate'};
            $last_area = $area;
            if (!$cronquiet) {
                print "xapian indexing $area $last_hdate\n";
            }
        }
        
        my $person_id = $$row{'person_id'};
        $person_id = "0" unless defined $person_id;

        my $subsection_or_id = $$row{'subsection_id'}==0 ? $$row{'epobject_id'} : $$row{'subsection_id'};

        my $date = $row->{hdate};
        $date =~ s/-//g;
        my $htime = $row->{htime} || 0;
        $htime =~ s/[^0-9]//g;

        my ($name, $party) = get_person($person_id, $date, $htime, $row->{major});

        my $dept = $$row{section_body} || '';
        $dept =~ s/[^a-z]//gi;

        my $doc = new Search::Xapian::Document();
        $termgenerator->set_document($doc);

        $doc->set_data($gid);

        $doc->add_term("Q$gid");
        $doc->add_term("B$new_indexbatch"); # For email alerts
        $doc->add_term("M$$row{major}");
        $doc->add_term("P\L$party") if $party;
        $doc->add_term("S$person_id");
        $doc->add_term("U$subsection_or_id"); # For searching within one debate
        $doc->add_term("D$date");
        $doc->add_term("G\L$dept") if $$row{major} == 3 || $$row{major} == 4 || $$row{major} == 8;
        $doc->add_term("C$$row{colnum}") if $$row{colnum};

        # For sort by date (although all wrans have same time of 00:00, no?)
        $doc->add_value(0, pack('N', $date+0) . pack('N', $htime+0));
        # XXX lenny Search::Xapian doesn't have sortable_serialise
        #my $datetimenum = "$date$htime";
        #$doc->add_value(0, Search::Xapian::sortable_serialise($datetimenum+0));
        $doc->add_value(1, $date); # For date range searches
        $doc->add_value(2, pack('N', $$row{'created'}) . pack('N', $$row{'hpos'})); # For email alerts
        $doc->add_value(3, $subsection_or_id); # For collapsing on segment

        $parser->parse($$row{'body'});
        $parser->eof();
        $termgenerator->increase_termpos();
        $termgenerator->index_text($name) if $name; # Index speaker name too so can search on them and words

        $db->replace_document_by_term("Q$gid", $doc);
    }

    # Now add Future Business to the index.
    my $fb_query = "SELECT id, body, chamber, event_date, committee_name, debate_type, title, witnesses, location, deleted, pos, unix_timestamp(modified) as modified FROM future";
    ($sdc = $since_date_condition) =~ s/{{date}}/event_date/g;
    $sdc =~ s/{{modified}}/modified/;
    $fb_query .= $sdc;

    my $fbq = $dbh->prepare($fb_query);
    $fbq->execute();

    my $people_query = "SELECT person_id FROM future_people WHERE calendar_id = ?";
    my $pq = $dbh->prepare($people_query);

    while (my $row = $fbq->fetchrow_hashref()) {
        my $xid = "calendar/$row->{id}";

        if ($row->{deleted}) {
            # Remove from Xapian if it's marked as deleted in the database
            $db->delete_document_by_term("Q$xid");
            next;
        }

        if ('calendar' ne $last_area) {
            $last_hdate = $row->{event_date};
            $last_area = 'calendar';
            if (!$cronquiet) {
                print "xapian indexing Future Business\n";
            }
        }

        my $date = $row->{event_date};
        $date =~ s/-//g;

        my $doc = new Search::Xapian::Document();
        $termgenerator->set_document($doc);

        $doc->set_data($xid);

        $doc->add_term("Q$xid");
        $doc->add_term("B$new_indexbatch"); # For email alerts
        $doc->add_term('MF'); # Mark it as Future Business
        $pq->execute($row->{id});
        while (my $personrow = $pq->fetchrow_hashref()) {
            $doc->add_term("S$personrow->{person_id}")
        }
        $doc->add_term("D$date");

        my $time_start = $row->{time_start} || 0;
        $time_start =~ s/[^0-9]//g;
        $doc->add_value(0, pack('N', $date+0) . pack('N', $time_start+0));
        $doc->add_value(1, $date); # For date range searches
        $doc->add_value(2, pack('N', $row->{modified}) . pack('N', $row->{pos})); # For email alerts

        $termgenerator->index_text($row->{title});
        if ($row->{witnesses}) {
            (my $witnesses = $row->{witnesses}) =~ s/<[^>]*>//; # Might be links
            $termgenerator->increase_termpos();
            $termgenerator->index_text($witnesses);
        }
        if ($row->{committee_name}) {
            $termgenerator->increase_termpos();
            $termgenerator->index_text($row->{committee_name});
        }

        $db->replace_document_by_term("Q$xid", $doc);
    }

    if ($last_hdate ne "") {
        print "\n" unless $cronquiet;
        # Store new batch number
        $dbh->do("insert into indexbatch (indexbatch_id, created) values (?, now())", {}, $new_indexbatch);
    }

    # Write out date we updated to, for 'sincefile' case
    if ($action eq "sincefile") {   
        # print "updating sincefile\n";
        open FH, ">$lastupdatedfile.tmp" or die "couldn't write to $lastupdatedfile.tmp";
        print FH "$now_start_string";
        close FH;
        rename "$lastupdatedfile.tmp", $lastupdatedfile;
    }

} elsif ($action eq 'check') {

    my $lastupdatedir = "$dbfile/../xml2db/";
    open FP, $lastupdatedir . 'deleted-gids' or exit;
    while (<FP>) {
        chomp;
        next unless $_;
        s#uk.org.publicwhip/#Q#;
        print "deleting $_ from Xapian index\n" unless $cronquiet;
        $db->delete_document_by_term($_);
    }
    # Wipe the file.
    open FP, '>' . $lastupdatedir . 'deleted-gids';
    close FP;

} elsif ($action eq 'checkfull') {

    # Look for deleted items, or items we've missed

    # Check for items in Xapian not in MySQL:
    my $q = $dbh->prepare('select gid from hansard where gid=?');
    my %xapian_gids;
    my $allterms = $db->allterms_begin();
    my $alltermsend = $db->allterms_end();
    while ($allterms ne $alltermsend) {
        my $term = "$allterms";
        if ($term =~ m#^Q#) {
            next if $term =~ /^Qcalendar/; # 13-Sept-2012, from production edit
            $q->execute('uk.org.publicwhip/' . substr($term, 1));
            my $exists = $q->fetchrow_arrayref();
            unless ($exists) {
                print "  deleting $term from Xapian index\n" unless $cronquiet;
#                $db->delete_document_by_term($term);
            }
            $xapian_gids{$term} = 1;
        }
        $allterms++;
    }

    # Check for items in MySQL not in Xapian
    $q = $dbh->prepare('select gid from hansard where year(hdate)=?');
    for (my $year = 1900; $year <= 2100; $year++) {
        $q->execute($year);
        while (my $row = $q->fetchrow_hashref()) {
            my $gid = $$row{'gid'};
            $gid =~ s#uk.org.publicwhip/#Q#;
            my $in_xapian = $xapian_gids{$gid};
            if (!$in_xapian) {
                # This is an internal error (or could happen if the MySQL database
                # updated while Xapian was reindexing, which the normal cron scripts
                # don't do).  Everything should have already been added by now, according
                # to the last modified logic above.
                print "  added $gid -- needs adding to Xapian indexing, but it should have been already by this point\n";
            }
        }
    }
}


sub get_person {
    my ($person_id, $hdate, $htime, $major) = @_;
    return unless $person_id;

    # Special exemptions for people 'speaking' after they have died
    # Note identical code to this in hansardlist.php
    $hdate = '20140907' if $person_id == 10170 && $hdate eq '20140908';
    $hdate = '20080813' if $person_id == 11068 && substr($hdate, 0, 6) eq '200809';
    $hdate = '20160616' if $person_id == 25394 && $hdate eq '20160701';

    my @matches = @{$dbh->selectall_arrayref($q_person, { Slice => {} }, $person_id, $hdate, $hdate, $hdate, $hdate)};
    if (@matches > 1) {
        @matches = grep { $major_to_house{$major}{$_->{house}} } @matches;
    }
    # Note identical code to this in hansardlist.php
    if (@matches > 1) {
        # Couple of special cases for the election of the NI Speaker
        if ($person_id == 13799 && $hdate eq '2007-05-08') {
            @matches = $matches[$htime < 1100 ? 0 : 1];
        }
        if ($person_id == 13831 && $hdate eq '2015-01-12') {
            @matches = $matches[$htime < 1300 ? 0 : 1];
        }
    }
    die "No options for person id $person_id on $hdate, major $major" if @matches < 1;
    die "Multiple options for person id $person_id on $hdate, major $major" if @matches > 1;

    my $member = $matches[0];
    my $name = '';
    if ($member->{house} == 2) {
        $name = $member->{title};
        $name .= " $member->{family_name}" if $member->{family_name};
        $name .= " of $member->{lordofname}" if $member->{lordofname};
    } else {
        $name = "$member->{given_name} $member->{family_name}";
        $name = "$member->{title} $name" if $member->{title};
    }
    return ($name, $member->{party});
}
