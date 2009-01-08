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
use lib "$FindBin::Bin/../../perllib";
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
    'check' to check everything is indexed
" if !$action or ($action ne "all" and $action ne "lastweek" and $action ne "lastmonth" and $action ne "sincefile" and $action ne "check" and $action ne "daterange");

# Open MySQL
my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASSWORD'), { RaiseError => 1, PrintError => 0 });

# Work out when to update from, for "sincefile" case
my $since_date_condition = "";
my $lastupdatedfile;
my $now_start_string;
if ($action eq "sincefile") {
    $lastupdatedfile = "$dbfile/twfy-lastupdated";
    if (-e $lastupdatedfile) {
        # Read unix time from file
        open FH, "<$lastupdatedfile" or die "couldn't open $lastupdatedfile even though it is there";
        $since_date_condition = " where hansard.modified >= from_unixtime('" . (readline FH) . "')";
        close FH;
    } else {
        # No file, update everything
        $since_date_condition = "";        
    }
    # Store time we need to update from next time
    my $sth = $dbh->prepare("select unix_timestamp(now())");
    $sth->execute();
    my @row = $sth->fetchrow_array();
    $now_start_string = $row[0];
}

# Date range case
my $datefrom;
my $dateto;
if ($action eq "daterange") {
    $datefrom=shift;
    die "As fourth parameter, specify from date in form 2001-06-01" if !$datefrom;
    $dateto=shift;
    die "As fifth parameter, specify to date in form 2004-10-28" if !$dateto;
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

if ($action ne "check") {
    # Batch numbers - each new stuff gets a new batch number
    my $max_indexbatch = $dbh->selectrow_array('select max(indexbatch_id) from indexbatch') || 0;
    my $new_indexbatch = $max_indexbatch + 1;
    
    # Get data for items to update from MySQL 
    # XXX unix_time is broken if htime>'23:59:59' (on long sittings)
    my $query = "select epobject.epobject_id, epobject.body, section.body as section_body,
        hdate, gid, major, section_id, subsection_id, colnum,
        unix_timestamp(concat(hdate, ' ', if(htime, htime, 0))) as unix_time,
        unix_timestamp(hansard.created) as created, hpos,
        person_id, member.title, first_name, last_name, constituency, party, house
        from epobject join hansard on epobject.epobject_id = hansard.epobject_id
	    left join member on hansard.speaker_id = member.member_id
        left join epobject as section on hansard.section_id = section.epobject_id";
    if ($action eq "lastweek") {
        $query .= " where hdate > date_sub(curdate(), interval 7 day)";
    } if ($action eq "lastmonth") {
        $query .= " where hdate > date_sub(curdate(), interval 1 month)";
    } elsif ($action eq "sincefile") {
        $query .= $since_date_condition;
    } elsif ($action eq "daterange") {
        $query .= " where hdate >= '$datefrom' and hdate <= '$dateto'";
    }
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

        my $date = $$row{'hdate'};
        $date =~ s/-//g;

        my $name = '';
        my $house = $$row{house} || -1;
        my ($first_name, $last_name, $constituency);
        if ($house > -1) {
            $first_name = decode_entities($$row{first_name});
            $last_name = decode_entities($$row{last_name});
            $constituency = decode_entities($$row{constituency});
        }
        if ($house == 1 || $house == 3 || $house == 4) {
            $name = "$first_name $last_name";
            $name = "$$row{title} $name" if $$row{title};
        } elsif ($house == 2) {
            $name = '';
            $name = 'the ' unless $last_name;
            $name .= $$row{title};
            $name .= " $last_name" if $last_name;
            $name .= " of $constituency" if $constituency;
        } elsif ($house == 0) { # Queen
            $name = "$first_name $last_name";
        }

        my $dept = $$row{section_body} || '';
        $dept =~ s/[^a-z]//gi;

        my $doc = new Search::Xapian::Document();
        $termgenerator->set_document($doc);

        $doc->set_data($gid);

        $doc->add_term("Q$gid");
        $doc->add_term("B$new_indexbatch"); # For email alerts
        $doc->add_term("M$$row{major}");
        $doc->add_term("P\L$$row{party}") if $$row{party};
        $doc->add_term("S$person_id");
        $doc->add_term("U$subsection_or_id"); # For searching within one debate
        $doc->add_term("D$date");
        $doc->add_term("G\L$dept") if $$row{major} == 3 || $$row{major} == 4 || $$row{major} == 8;
        $doc->add_term("C$$row{colnum}") if $$row{colnum};

        my $packedUnixTime = pack('N', $$row{'unix_time'});
        $doc->add_value(0, $packedUnixTime); # For sort by date (although all wrans have same time of 00:00, no?)
        $doc->add_value(1, $date); # For date range searches
        $doc->add_value(2, pack('N', $$row{'created'}) . pack('N', $$row{'hpos'})); # For email alerts
        $doc->add_value(3, $subsection_or_id); # For collapsing on segment

        $parser->parse($$row{'body'});
        $parser->eof();
        $termgenerator->increase_termpos();
        $termgenerator->index_text($name) if $name; # Index speaker name too so can search on them and words

        $db->replace_document_by_term("Q$gid", $doc);
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
} else {
    # Look for deleted items, or items we've missed
    # .. fetch all gids in MySQL
    my $query = "select gid from hansard";
    my $q = $dbh->prepare($query);
    $q->execute();
    my %mysql_gids;
    while (my $row = $q->fetchrow_hashref()) {
        my $gid = $$row{'gid'};
        $gid =~ s#uk.org.publicwhip/##;
        $mysql_gids{"Q$gid"} = 1;
    }
    # .. fetch all gids in Xapian
    my %xapian_gids;
    my $allterms = $db->allterms_begin(); # 'Q');
    my $alltermsend = $db->allterms_end(); # 'Q');
    while ($allterms ne $alltermsend) {
        my $term = "$allterms";
        if ($term =~ m#^Q#) { # (?:wrans|debate|westminhall|wms|lords|ni|standing)/#) {
            $xapian_gids{$term} = 1;
        }
        $allterms++;
    }
    # Compare them. 
    # Check for items in Xapian not in MySQL:
    foreach my $xapian_gid (keys %xapian_gids) {
        my $in_mysql = $mysql_gids{$xapian_gid};
        if (!$in_mysql) {
            print "deleting $xapian_gid from Xapian index\n" unless $cronquiet;
            $db->delete_document_by_term($xapian_gid);
        }
    }
    # Check for items in MySQL not in Xapian
    foreach my $mysql_gid (keys %mysql_gids) {
        my $in_xapian = $xapian_gids{$mysql_gid};
        if (!$in_xapian) {
            # This is an internal error (or could happen if the MySQL database
            # updated while Xapian was reindexing, which the normal cron scripts
            # don't do).  Everything should have already been added by now, according
            # to the last modified logic above.
            print "added $mysql_gid -- needs adding to Xapian indexing, but it should have been already by this point\n";
        }
    }
}
