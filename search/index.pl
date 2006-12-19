#!/usr/bin/perl -w
# vim:sw=4:ts=4:et:nowrap

# Indexer of TheyWorkForYou.com using Xapian.

use strict;
use Carp;
use Search::Xapian qw(:standard);
use HTML::Parser;
use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin" . "/../scripts";
use config;
use DBI;
use POSIX qw(strftime);
use Data::Dumper;
#DBI->trace(2);

# Command line parser
$|=1;
my $dbfile=shift;
die "Specify Xapian database file as first parameter" if !$dbfile;
my $action=shift;
die "As second parameter, specify:
    'all' to (re)index everything (sometimes memory leaks and doesn't work see indexall.sh), or 
    'lastweek' to (re)index just the last week, or 
    'lastmonth' to (re)index just the last month, or 
    'daterange' to (re)index between two dates, specified as next parameters
    'sincefile' to (re)index updates since a given date (specified in unixtime inside a file as 
                   the last parameter, the file is updated to now after indexing)
    'check' to check everything is indexed
" if !$action or ($action ne "all" and $action ne "lastweek" and $action ne "lastmonth" and $action ne "sincefile" and $action ne "check" and $action ne "daterange");

# Open MySQL
my $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });

# Work out when to update from, for "sincefile" case
my $since_date_condition = "";
my $lastupdatedfile;
my $now_start_string;
if ($action eq "sincefile") {
    $lastupdatedfile=shift;
    die "As third parameter, specify a file used by this script to store when the last update was" if !$lastupdatedfile;

    if (-e $lastupdatedfile) {
        # Read unix time from file
        open FH, "<$lastupdatedfile" or die "couldn't open $lastupdatedfile even though it is there";
        $since_date_condition = " and hansard.modified >= from_unixtime('" . (readline FH) . "')";
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
#$::Stemmer=new Search::Xapian::Stem('english');
my $db=Search::Xapian::WritableDatabase->new($dbfile, Search::Xapian::DB_CREATE_OR_OPEN);

if ($action ne "check") {
    # Batch numbers - each new stuff gets a new batch number
    my $sth = $dbh->prepare("insert into indexbatch (created) values (now())");
    $sth->execute();
    $sth = $dbh->prepare("select last_insert_id()");
    $sth->execute();
    my @row = $sth->fetchrow_array();
    my $new_indexbatch = $row[0];

    # Get data for items to update from MySQL 
    my $query = "select epobject.epobject_id, body, person_id, hdate, gid, major, 
        section_id, subsection_id, party,
        unix_timestamp(concat(hdate, ' ', if(htime, htime, 0))) as unix_time,
        unix_timestamp(hansard.created) as created, hpos
        from epobject, hansard 
	    left join member on hansard.speaker_id = member.member_id
	    where epobject.epobject_id = hansard.epobject_id";
    if ($action eq "lastweek") {
        $query .= " and hdate > date_sub(curdate(), interval 7 day)";
    } if ($action eq "lastmonth") {
        $query .= " and hdate > date_sub(curdate(), interval 1 month)";
    } elsif ($action eq "sincefile") {
        $query .= $since_date_condition;
    } elsif ($action eq "daterange") {
        $query .= " and hdate >= '$datefrom' and hdate <= '$dateto'";
    }
    $query .= ' and major = ' . $section if ($section);
    $query .= ' ORDER BY hdate,major,hpos';
    my $q = $dbh->prepare($query);
    $q->execute();
    #print "indexing with Xapian, rows from mysql: " . $q->rows();

    # Loop through all the rows from MySQL
    my $parser=new HTML::Parser();
    $parser->handler(text => \&process_text);
    croak if !$db;
    my $last_hdate = "";
    my $last_area = "";
    while (my $row = $q->fetchrow_hashref()) {
        # Process data from MySQL
        my $gid = $$row{'gid'};
        $gid =~ s#uk.org.publicwhip/##;

        my $person_id = $$row{'person_id'};
        if (! defined $person_id) {
            $person_id = "0";
        }
        $person_id = "none" if ($person_id eq "0");

        $gid =~ m#(.*)/#;
        my $area = $1; # wrans or debate or westminhall or wms
        if ($$row{'hdate'} ne $last_hdate || $area ne $last_area) {
            $last_hdate = $$row{'hdate'};
                $last_area = $area;
            if (!$cronquiet) {
                print "xapian indexing $area $last_hdate\n";
            }
        }
        #print "$gid $person_id $$row{'major'}\n";
        
        # Make new post for this item in Xapian
        $::doc=new Search::Xapian::Document();
        #print Dumper($row);
        #print $gid . "\n";

        $::doc->set_data($gid);
        $::doc->add_term("speaker:" . $person_id);
        $::doc->add_term("major:" . $$row{'major'});
        $::doc->add_term("batch:" . $new_indexbatch);
        # XXX someone requested party here (remember to lowercase it)
        # (And standardise on something - e.g. MLA party names aren't the
        # same as MPs; *and* use "P:" rather than "party:" or similar)
#        my $ddd = $$row{'hdate'};
#        $ddd =~ s/-//g;
#        $::doc->add_term('date:' . $ddd);
        $::doc->add_term($gid);


        # left pad the unix time stamp with 0's. This field is
        # intended to be used for ordering search results using
        # Enquire::set_sorting, which uses lexicographical (string)
        # ordering and not numeric ordering. Therefore, left padding
        # ensures the correct ordering ("02" comes before "10" in
        # lexicographic order, wheras "2" comes after "10")
        my $leftPaddedUnixTime=sprintf('%010s', $$row{'unix_time'});
        $::doc->add_value(0, $leftPaddedUnixTime);
        $::doc->add_value(2, $$row{'hdate'});
        $::doc->add_value(3, $$row{'section_id'});
        $::doc->add_value(4, $$row{'subsection_id'});
        $::doc->add_value(5, lc($$row{'party'}));
        $::doc->add_value(6, sprintf('%010s', $$row{'created'}) . ':' . sprintf('%05s', $$row{'hpos'}));
        $::doc->add_value(7, $$row{'section_id'} . ':' . ($$row{'subsection_id'}==0?$$row{'epobject_id'}:$$row{'subsection_id'}));
        $::n=1;
        $parser->parse($$row{'body'}); 
        $parser->eof();

        # See if we already have the document in Xapian
        #print "$gid\n";
        my $docid = gid_to_docid($gid);
        if (defined $docid) {
            $db->replace_document($docid, $::doc);
            #print '.'; # progress marker (replace)
        } else {
            $db->add_document($::doc);
            #print '+'; # progress marker (append)
        }
    }
    if ($last_hdate ne "") {
        if (!$cronquiet) {
            print "\n";
        }
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
    # .. fetch all gids in Xapian
    my %xapian_gids;
    my $allterms = $db->allterms_begin();
    my $alltermsend = $db->allterms_end();
    while ($allterms ne $alltermsend) {
        my $term = "$allterms";
        if ($term =~ m#(?:wrans|debate|westminhall|wms|lords|ni)/#) {
            $xapian_gids{$term} = 1;
        }
        $allterms++;
    }
    # .. fetch all gids in MySQL
    my $query = "select gid from hansard";
    my $q = $dbh->prepare($query);
    $q->execute();
    my %mysql_gids;
    while (my $row = $q->fetchrow_hashref()) {
        my $gid = $$row{'gid'};
        $gid =~ s#uk.org.publicwhip/##;
        $mysql_gids{$gid} = 1;
    }
    # Compare them. 
    # Check for items in Xapian not in MySQL:
    foreach my $xapian_gid (keys %xapian_gids) {
        my $in_mysql = $mysql_gids{$xapian_gid};
        if (!$in_mysql) {
#            print "deleting $xapian_gid from Xapian index\n" unless $cronquiet;
            my $docid = gid_to_docid($xapian_gid);
            if (defined $docid) {
                $db->delete_document($docid);
            } else {
                die "Xapian id for $xapian_gid disappeared under my feet\n";
            }
        }
    }
    # Check for items in MySQL not in Xapian
    foreach my $mysql_gid (keys %mysql_gids) {
        my $in_xapian = $xapian_gids{$mysql_gid};
        if (!$in_xapian) { # && $mysql_gid !~ /lords/) {
            # This is an internal error (or could happen if the MySQL database
            # updated while Xapian was reindexing, which the normal cron scripts
            # don't do).  Everything should have already been added by now, according
            # to the last modified logic above.
            print "added $mysql_gid -- needs adding to Xapian indexing, but it should have been already by this point\n";
        }
    }
}

# Does the actual adding (this is a handler for the HTML parser)
sub process_text {
    my $p=shift;
    my $text=shift;

    $text =~ s/&#\d+;/ /g;
    $text =~ s/(\d),(\d)/$1$2/g;
    $text =~ s/[^A-Za-z0-9]/ /g;
    my @words = split /\s+/,$text;

    foreach my $word (@words) {
        next if $word eq '';
        next if $word =~ /^\d{1,2}$/;
        my $lowerword=lc $word;
        $::doc->add_posting("$lowerword",$::n);
        #print "added word $lowerword\n";
        # no stemming now
        #my $stemword=$::Stemmer->stem_word($lowerword);
        #$::doc->add_posting("$stemword",$::n);
        $::n++;
    }
}

sub gid_to_docid
{
    my $gid = shift;
    my $post = $db->postlist_begin($gid);
    my $postend = $db->postlist_end($gid);
    my $docid = undef;
    while ($post ne $postend) {
        die "gid $gid in xapian db twice" if (defined $docid);
        $docid = $post;
        $post++;
    }
    return $docid;
}

