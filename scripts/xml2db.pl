#! /usr/bin/perl -w

use strict;

# Loads XML written answer/debate/etc files into TheyWorkForYou.
# 
# Magic numbers, and other properties of the destination schema
# used to be documented here:
#    http://web.archive.org/web/20090414002944/http://wiki.theyworkforyou.com/cgi-bin/moin.cgi/DataSchema
# ... although please be aware that (as the archive.org URL suggests)
# that document is no longer maintained and contains out-of-date information.
# For some of the other magic numbers, you can refer to
# www/includes/dbtypes.php in this repository, which should be current.
#
# The XML files for Hansard objects come from the Public Whip parser:
#       https://github.com/mysociety/parlparse/tree/master/pyscraper

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../commonlib/perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $parldata = mySociety::Config::get('RAWDATA');
my $lastupdatedir = mySociety::Config::get('INCLUDESPATH') . "../../../xml2db/";

use DBI; 
use File::Slurp;
use HTML::Entities;
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

use vars qw($all $recent $date $datefrom $dateto $wrans $debates $westminhall
    $wms $lordsdebates $ni $force $quiet $cronquiet $standing
    $scotland $scotwrans $scotqs %scotqspreloaded
);
my $result = GetOptions ( "all" => \$all,
            "recent" => \$recent,
            "date=s" => \$date,
            "from=s" => \$datefrom,
            "to=s" => \$dateto,
            "wrans" => \$wrans,
            "westminhall" => \$westminhall,
            "debates" => \$debates,
            "wms" => \$wms,
            "lordsdebates" => \$lordsdebates,
            "ni" => \$ni,
            "scotland" => \$scotland,
            "scotwrans" => \$scotwrans,
            "scotqs" => \$scotqs,
            "standing" => \$standing,
            "force" => \$force,
            "quiet" => \$quiet,
            "cronquiet" => \$cronquiet,
            );

my $c = 0;
$c++ if $all;
$c++ if $recent;
$c++ if $date;
$c++ if ($datefrom || $dateto);

if ((!$result) || ($c != 1) || (!$debates && !$wrans && !$westminhall && !$wms && !$lordsdebates && !$ni && !$standing && !$scotland && !$scotwrans && !$scotqs))
{
print <<END;

Loads XML files from the parldata directory into TheyWorkForYou.
The input files contain debates, written answers and so on, and were generated
by pyscraper from parlparse. This script synchronises the database to the
files, so existing entries with the same gid are updated preserving their
database id. 

--wrans - process Written Answers (C&L)
--debates - process Commons Debates
--westminhall - process Westminster Hall
--wms - process Written Ministerial Statements (C&L)
--lordsdebates - process Lords Debates
--ni - process Northern Ireland Assembly debates
--scotland  - process Scottish Parliament debates
--scotwrans - process Scottish Parliament written answers
--scotqs - process mentions of Scottish Parliament question IDs
--standing - process Public Bill Commitees (Standing Committees as were)

--recent - acts incrementally, using -lastload files
--all - reprocess every single date back in time
--date=YYYY-MM-DD - reprocess just this date
--from=YYYY-MM-DD --to=YYYY-MM-DD - reprocess this date range

--force - also delete items from database that weren't in the XML
      file (applied per day only)
--quiet - don't print the contents whenever an existing entry is 
      modified or deleted
--cronquiet - stop printing date names as entries are processed

END
    exit;
}

if ($datefrom || $dateto)
{
    $datefrom = "1000-01-01" if !defined $datefrom;
    $dateto = "9999-12-31" if !defined $dateto;
}
else
{
    $datefrom = "9999-12-31";
    $dateto = "1000-01-01";
}
if ($date)
{
    $dateto = $date;
    $datefrom = $date;
}

db_connect();

##########################################################################

use vars qw($hpos $curdate);
use vars qw($currsection $currsubsection $inoralanswers $promotedheading);
use vars qw($currmajor $currminor);
use vars qw(%gids %grdests %ignorehistorygids $tallygidsmode $tallygidsmodedummycount);
use vars qw(%membertoperson %personredirect);
use vars qw($current_file);

my %scotland_vote_store = (
    for => 'aye',
    against => 'no',
    abstentions => 'both',
);

use vars qw($debatesdir $wransdir $lordswransdir $westminhalldir $wmsdir
    $lordswmsdir $lordsdebatesdir $nidir $standingdir $scotlanddir
    $scotwransdir $scotqsdir
);
$debatesdir = $parldata . "scrapedxml/debates/";
$wransdir = $parldata . "scrapedxml/wrans/";
$lordswransdir = $parldata . "scrapedxml/lordswrans/";
$westminhalldir = $parldata . "scrapedxml/westminhall/";
$wmsdir = $parldata . "scrapedxml/wms/";
$lordswmsdir = $parldata . "scrapedxml/lordswms/";
$lordsdebatesdir = $parldata . "scrapedxml/lordspages/";
$nidir = $parldata . 'scrapedxml/ni/';
$scotlanddir = $parldata . 'scrapedxml/sp-new/meeting-of-the-parliament/';
$scotwransdir = $parldata . 'scrapedxml/sp-written/';
$standingdir = $parldata . 'scrapedxml/standing/';
$scotqsdir = $parldata . 'scrapedxml/sp-questions/';

my @wrans_major_headings = (
"ADVOCATE-GENERAL", "ADVOCATE GENERAL", "ADVOCATE-GENERAL FOR SCOTLAND", "AGRICULTURE, FISHERIES AND FOOD",
"ATTORNEY-GENERAL", "CABINET OFFICE", "CABINET", "CULTURE MEDIA AND SPORT", "CULTURE, MEDIA AND SPORT",
"CULTURE, MEDIA AND SPORTA", "CULTURE, MEDIA, SPORT", "CHURCH COMMISSIONERS", "CHURCH COMMISSIONER",
"COMMUNITIES AND LOCAL GOVERNMENT", "CONSTITUTIONAL AFFAIRS", "CONSTITIONAL AFFAIRS", "CONSTITUTIONAL AFFFAIRS",
"DEFENCE", "DEPUTY PRIME MINISTER", "DUCHY OF LANCASTER", "EDUCATION AND EMPLOYMENT", "ENVIRONMENT FOOD AND RURAL AFFAIRS",
"ENVIRONMENT, FOOD AND RURAL AFFAIRS", "DEFRA", "ENVIRONMENT, FOOD AND THE REGIONS", "ENVIRONMENT",
"EDUCATION AND SKILLS", "EDUCATION", "ELECTORAL COMMISSION COMMITTEE", "ELECTORAL COMMISSION",
"SPEAKER'S COMMITTEE ON THE ELECTORAL COMMISSION", "FOREIGN AND COMMONWEALTH AFFAIRS", "FOREIGN AND COMMONWEALTH",
"FOREIGN AND COMMONWEALTH OFFICE", "HOME DEPARTMENT", "HOME OFFICE", "HOME", "HEALTH", "HOUSE OF COMMONS",
"HOUSE OF COMMONS COMMISSION", "HOUSE OF COMMMONS COMMISSION", "INTERNATIONAL DEVELOPMENT", "INTERNATIONAL DEVEOPMENT",
"JUSTICE", "LEADER OF THE HOUSE", "LEADER OF THE COUNCIL", "LORD CHANCELLOR", "LORD CHANCELLOR'S DEPARTMENT",
"LORD CHANCELLORS DEPARTMENT", "LORD CHANCELLOR'S DEPT", "CHANCELLOR OF THE DUCHY OF LANCASTER",
"LORD PRESIDENT OF THE COUNCIL", "MINISTER FOR WOMEN",
"WOMEN", "NATIONAL HERITAGE", "NORTHERN IRELAND", "OVERSEAS DEVELOPMENT ADMINISTRATION", "PRIME MINISTER",
"PRIVY COUNCIL", "PRIVY COUNCIL OFFICE", "PRESIDENT OF THE COUNCIL", "PUBLIC ACCOUNTS COMMISSION",
"PUBLIC ACCOUNTS COMMITTEE", "SOLICITOR-GENERAL", "SOLICITOR GENERAL", "SCOTLAND", "SOCIAL SECURITY", "TRANSPORT",
"TRANSPORT, LOCAL GOVERNMENT AND THE REGIONS", "TRADE AND INDUSTRY", "TREASURY", "WALES", "WORK AND PENSIONS",
"INNOVATION, UNIVERSITIES AND SKILLS", "CHILDREN, SCHOOLS AND FAMILIES",
"BUSINESS, ENTERPRISE AND REGULATORY REFORM", "BUSINESS, INNOVATION AND SKILLS",
);
use vars qw($wrans_major_headings);
$wrans_major_headings = ',' . join(',', @wrans_major_headings) . ',';

# Do dates in reverse order
sub revsort {
    return reverse sort @_;
}

# Process debates or wrans etc
sub process_type {
    my ($xnames, $xdirs, $xdayfunc) = @_;

    my $process;
    my $xsince = 0;
    if (open FH, '<' . $lastupdatedir . $xnames->[0] . '-lastload') {
        $xsince = readline FH;
        close FH;
    }
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
            my $use = ($stat[9] >= $xsince);
            my $date_part = $1;
    
            if ($xmaxtime[$i] < $stat[9]) {
                $xmaxfile = $xfile;
                $xmaxtime[$i] = $stat[9];
            }

            #print $xfile ." ".($use?"t":"f")." $xsince $stat[9]\n";
            if ($all || ($use && $recent) || ($datefrom le $date_part && $date_part le $dateto)) {
                $process->{$date_part} = 1;
            }
        };
        find({ wanted=>$xwanted, preprocess=>\&revsort }, $xdir);
    }

    # Go through dates, and load each one
    my $xname = join(',', @$xnames);
    foreach my $process_date (sort keys %$process) {
        if (!$cronquiet) {
            print "db loading $xname $process_date\n";
        }
        &$xdayfunc($process_date);
        # So we don't do it again
        # XXX Doesn't currently apply to any files
        #my $xfile = "$process_date.xml";
        #foreach my $xdir (@$xdirs) {
        #    utime(($xsince - 1), ($xsince - 1), ($xdir.$xfile));
        #}
    }

    # Store that we've done
    if ($recent) {
        my $xxmaxtime = 0;
        for (my $i=0; $i<@$xdirs; $i++) {
            my $xdir = $xdirs->[$i];
            (my $sxdir = $xdir) =~ s/lords(wrans|wms)/lordspages/;
            # Find last update time
            die "xmaxtime[$i] not initialised" unless $xmaxtime[$i];
            if ($xxmaxtime < $xmaxtime[$i]) {
                $xxmaxtime = $xmaxtime[$i];
            }
        }
       
        if ($xxmaxtime != $xsince) {
            # We use the current maxtime, so we run things still at that time again
            # (when there was an rsync from parlparse it might have only got one of
            # two files set in # the same second, and next time it might get the other)
            #print "$xname since: $xsince new max $xmaxtime from changedates\n";
            my $xname = $xnames->[0];
            open FH, ">$lastupdatedir$xname-lastload" or die "couldn't open $lastupdatedir$xname-lastload for writing";
            print FH $xxmaxtime;
            close FH;
        }
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
process_type(["answers", "lordswrans"], [$wransdir, $lordswransdir], \&add_wrans_day) if ($wrans);
process_type(["westminster"], [$westminhalldir], \&add_westminhall_day) if ($westminhall);
process_type(["ministerial", "lordswms"], [$wmsdir, $lordswmsdir], \&add_wms_day) if ($wms);
process_type(["daylord"], [$lordsdebatesdir], \&add_lordsdebates_day) if ($lordsdebates);
process_type(['ni'], [$nidir], \&add_ni_day) if ($ni);
process_type(['sp'], [$scotlanddir], \&add_scotland_day) if $scotland;
process_type(['spwa'], [$scotwransdir], \&add_scotwrans_day) if $scotwrans;
process_type(['standing'], [$standingdir], \&add_standing_day) if $standing;

# Process the question mentions for the Scottish Parliament
process_mentions('spq', $scotqsdir) if $scotqs;

##########################################################################
# Utility

# Parse all the files which match the glob using twig.
sub parsefile_glob {
    my ($twig, $glob) = @_;
    my @files = glob($glob);
    %ignorehistorygids = ();
    foreach (@files) {
        $current_file = $_;
        #print "twigging: $_\n";
        $twig->parsefile($_);
    }
}

##########################################################################
# Database

my ($dbh, 
    $epadd, $epcheck, $epupdate,
    $hadd, $hcheck, $hupdate, $hdelete, $hdeletegid,
    $divisionupdate, $voteupdate,
    $gradd, $grdeletegid,
    $scotqadd, $scotqdelete, $scotqbusinessexist, $scotqholdingexist,
    $scotqdategidexist, $scotqreferenceexist,
    $lastid);

sub db_connect
{
    # Connect to database, and prepare queries
    my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
    $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0, , mysql_enable_utf8 => 1 });

    # epobject queries
    $epadd = $dbh->prepare("insert into epobject (title, body, type, created, modified)
        values ('', ?, 1, NOW(), NOW())");
    $epcheck = $dbh->prepare("select body from epobject where epobject_id = ?");
    $epupdate = $dbh->prepare("update epobject set body = ?, modified = NOW() where epobject_id = ?");

    # hansard object queries
    $hadd = $dbh->prepare("insert into hansard (epobject_id, gid, colnum, htype, person_id, major, minor, section_id, subsection_id, hpos, hdate, htime, source_url, created, modified)
        values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $hcheck = $dbh->prepare("select epobject_id, gid, colnum, htype, person_id, major, minor, section_id, subsection_id, hpos, hdate, htime, source_url from hansard where gid = ?");
    $hupdate = $dbh->prepare("update hansard set gid = ?, colnum = ?, htype = ?, person_id = ?, major = ?, minor = ?, section_id = ?, subsection_id = ?, hpos = ?, hdate = ?, htime = ?, source_url = ?, modified = NOW()
        where epobject_id = ? and gid = ?");
    $hdelete = $dbh->prepare("delete from hansard where gid = ? and epobject_id = ?");
    $hdeletegid = $dbh->prepare("delete from hansard where gid = ?");

    # Divisions
    $divisionupdate = $dbh->prepare("INSERT INTO divisions (division_id, house, division_title, yes_text, no_text, division_date, division_number, gid, yes_total, no_total, absent_total, both_total, majority_vote) VALUES (?, ?, ?, '', '', ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE gid=VALUES(gid), yes_total=VALUES(yes_total), no_total=VALUES(no_total), absent_total=VALUES(absent_total), both_total=VALUES(both_total), majority_vote=VALUES(majority_vote)");
    $voteupdate = $dbh->prepare("INSERT INTO persondivisionvotes (person_id, division_id, vote) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE vote=VALUES(vote)");

    # gidredirect entries
    $gradd = $dbh->prepare("replace into gidredirect (gid_from, gid_to, hdate, major) values (?,?,?,?)");
    $grdeletegid = $dbh->prepare("delete from gidredirect where gid_from = ?");

    # scottish question mentions
    $scotqadd = $dbh->prepare("insert into mentions (gid, type, date, url, mentioned_gid) values (?,?,?,?,?)");
    $scotqbusinessexist = $dbh->prepare("select mention_id from mentions where gid = ? and type = ? and date = ? and url = ?");
    $scotqholdingexist = $dbh->prepare("select mention_id from mentions where gid = ? and type = ? and date = ?");
    $scotqdategidexist = $dbh->prepare("select mention_id from mentions where gid = ? and type = ? and date = ? and mentioned_gid = ?");
    $scotqreferenceexist = $dbh->prepare("select mention_id from mentions where gid = ? and type = ? and mentioned_gid = ?");
    $scotqdelete = $dbh->prepare("delete from mentions where mention_id = ?");

    # other queries
    $lastid = $dbh->prepare("select last_insert_id()");

    # Clear any half made previous attempts.
    delete_lonely_epobjects()
}

# Process a file of "mentions"...
sub process_mentions {
    my ($xgidtype,$xdir) = @_;

    # Nasty cut-and-pasting of process_type, more or less:

    if ($xgidtype eq "spq") {

        # Checking one-by-one which rows are already in the DB is
        # horrendously slow, so we could load in the entire table as
        # the first thing we do:

        my $preload_table = 1;
        if ($preload_table) {
            %scotqspreloaded = ();
            my $scotqsall = $dbh->prepare("select mention_id, gid, type, date, url, mentioned_gid from mentions");
            my $rows = $scotqsall->execute();
            while (my @row = $scotqsall->fetchrow_array()) {
                my $s = join('|', map { defined $_ ? $_ : '' } @row[1..5]);
                $scotqspreloaded{$s} = 1;
            }
        }

        my $process;
        my $xsince = 0;
        if (open FH, '<' . $lastupdatedir . $xgidtype . '-lastload') {
            $xsince = readline FH;
            close FH;
        }

        my $xmaxtime = 0;
        my $xmaxfile = "";

        # Record which dates have files which have been updated:

        my $xwanted = sub {
            return unless /^up-to-(\d{4}-\d\d-\d\d)(.*)\.xml$/;
            my $xfile = $_;
            my @stat = stat($xdir . $xfile);
            my $use = ($stat[9] >= $xsince);
            my $date_part = $1;

            if ($xmaxtime < $stat[9]) {
                $xmaxfile = $xfile;
                $xmaxtime = $stat[9];
            }

            #print $xfile ." ".($use?"t":"f")." $xsince $stat[9]\n";
            if ($all || ($use && $recent) || ($datefrom le $date_part && $date_part le $dateto)) {
                $process->{$date_part} = $xfile;
            }
        };

        find({ wanted=>$xwanted, preprocess=>\&revsort }, $xdir);

        # Go through dates, and load each one
        foreach my $process_date (sort keys %$process) {
            my $xfile = $process->{$process_date};
            if (!$cronquiet) {
                print "db loading $process_date (file: $xfile)\n";
            }
            my $twig = XML::Twig->new(twig_handlers =>
                { 'question' => \&loadspq });
            $twig->parsefile($xdir . $xfile);
            $twig->dispose();
        }

        # Store that we've done
        if ($recent) {
            die "xmaxtime not initialised" unless $xmaxtime;
            if ($xmaxtime != $xsince) {
                open FH, ">$lastupdatedir$xgidtype-lastload" or die "couldn't open $lastupdatedir$xgidtype-lastload for writing";
                print FH $xmaxtime;
                close FH;
            }
        }

    } else {
        die "Unknown gid type in process_mentions ($xgidtype)"
    }
}

sub db_disconnect
{
    $dbh->disconnect();
}

sub delete_lonely_epobjects()
{
    # We assume all epobjects are type 1, i.e. have hansard table entries for now
    my $r = $dbh->selectcol_arrayref("select count(*) from epobject where type <> 1;");
    my $c = $r->[0];
    die "Unknown type not 1 entries in epobject table, lots of code needs fixing" if $c > 0;

    # Quick check using counts
    my $r1 = $dbh->selectcol_arrayref("select count(*) from epobject");
    my $c1 = $r1->[0];
    my $r2 = $dbh->selectcol_arrayref("select count(*) from hansard");
    my $c2 = $r2->[0];
    return if $c2 == $c1;
    
    print "Fixing up lonely epobjects. Counts: $c1 $c2\n" unless $cronquiet;
    my $q = $dbh->prepare("select epobject_id from epobject");
    $q->execute();
    my $left;
    while (my @row = $q->fetchrow_array) {
        $left->{$row[0]} = 1;
    }
    $q = $dbh->prepare("select epobject_id from hansard");
    $q->execute();
    while (my @row = $q->fetchrow_array) {
        delete($left->{$row[0]});
    }

    my @array = keys(%$left);
    my $rows = @array;
    print "Lonely epobject count: $rows\n" unless $cronquiet;
    if ($rows > 0) {
        my $delids = join(", ", @array);
        my $qq = $dbh->prepare("delete from epobject where epobject_id in (" . $delids . ")");
        my $delrows = $qq->execute();
        $qq->finish();
        die "deleted " . $delrows . " but thought " . $rows if $delrows != $rows;
    }
    $q->finish();
}

# Check that there are no extra gids in db that weren't in xml
sub check_extra_gids
{
    my $date = shift;
    my $gidsref = shift;
    my $where = shift;

    my $q = $dbh->prepare("select gid from hansard where hdate = ? and gid not like '%L' and $where");
    my $rows = $q->execute($date);
    my $array_ref1 = $q->fetchall_arrayref();
    $q->finish();
    $q = $dbh->prepare("select gid_from from gidredirect where hdate = ? and $where");
    $rows = $q->execute($date);
    my $array_ref2 = $q->fetchall_arrayref();
    $q->finish();

    my @mysql_gids1 = map $_->[0], @$array_ref1;
    my @mysql_gids2 = map $_->[0], @$array_ref2;
    my @mysql_allgids = sort(@mysql_gids1, @mysql_gids2);

    my @xml_gids = sort @$gidsref;

    # Find items in MySQL which aren't in XML -- this shouldn't
    # happen, the Public Whip parser should never allow it.  This
    # code is partly a double check.
    my %xml_hash;
    foreach my $gid (@xml_gids) {
        $xml_hash{$gid} = 1; 
    }
    my $missing = 0;
    foreach my $gid (@mysql_allgids) {
        if (!$xml_hash{$gid}) {
            # in MySQL, not in XML
            $missing++;
            my $vital = 0;
            # check no comments, votes etc.
            for my $entry (["comments", "epobject_id",], 
                       ["anonvotes", "epobject_id",],
                       ["uservotes", "epobject_id",],
                       ["editqueue", "epobject_id_l",],
                       ["editqueue", "epobject_id_h",],
                       ) {
                my ($table, $field) = @$entry;
                my $epuse_comments = $dbh->prepare("select count(*) from epobject, hansard, $table
                    where epobject.epobject_id = $table.$field and epobject.epobject_id = hansard.epobject_id and
                    hansard.gid = ?");
                $epuse_comments->execute($gid);
                my $num_rows = $epuse_comments->fetchrow_array();
                $epuse_comments->finish();
                if ($num_rows > 0) {
                    if ($gid =~ /wrans/ && !$cronquiet) {
                        my $search_gid = $gid;
                        $search_gid =~ s/(\d\d\d\d-\d)\d-\d\d\w(\.\d+\.)/$1%$2/;
                        my $daychange = $dbh->prepare('SELECT gid,epobject_id FROM hansard WHERE gid like ? AND gid != ?');
                        $daychange->execute($search_gid, $gid);
                        my ($new_gid, $new_epobjectid) = $daychange->fetchrow_array();
                        if ($new_epobjectid) {
                            my $hgetid = $dbh->prepare("select epobject_id from hansard where gid = ?");
                            $hgetid->execute($gid);
                            my $old_epobjectid = $hgetid->fetchrow_array();
                            $hgetid->finish();
                            print "POSSIBLE FIX: $gid -> $new_gid, $old_epobjectid -> $new_epobjectid ?\n";
                            my $yes = <STDIN>;
                            if ($yes =~ /^y$/i) {
                                update_eid($table, $field, $old_epobjectid, $new_epobjectid);
                                next;
                            }
                        }
                    }                
                    print "VITAL ERROR! gid $gid needs deleting, has an entry in table $table, but no gid redirect\n";
                    $vital++;
                }
            }
            # either fix it, or display it
            if ($force) {
                if ($vital > 0) { 
                    die "Refusing to even force delete, when there are references in other tables\n";
                } else {
                    $hdeletegid->execute($gid);
                    $hdeletegid->finish();
                    $grdeletegid->execute($gid);
                    $grdeletegid->finish();
                    print "FORCED deleting $gid from db, wasn't in XML\n";
                }
            }
            else {
                print "gid $gid in database not in XML, run again with --force to delete\n";
            }
        }
    }
    if ($missing) {
        if ($force) {
            delete_lonely_epobjects();
        } else {
            die;
        }
    }
}

sub delete_redirected_gids {
    my ($date, $grdests) = @_;
    my $q_redirect = $dbh->prepare('SELECT gid_to from gidredirect WHERE gid_from = ?');
    my $hgetid = $dbh->prepare("select epobject_id from hansard where gid = ?");
    open FP, '>>' . $lastupdatedir . 'deleted-gids';
    foreach my $from_gid (sort keys %$grdests) {
        my $to_gid = $grdests->{$from_gid}[0];
        my $matchtype = $grdests->{$from_gid}[1];
        my $loop;
        do {
            $loop = 0;
            $q_redirect->execute($to_gid);
            my $lookup = $q_redirect->fetchrow_array();
            if ($lookup) {
                $loop = 1;
                $to_gid = $lookup;
            }
        } while ($loop);
        $hcheck->execute($to_gid);
        my $new_epobjectid = ($hcheck->fetchrow_array())[0];
        $hcheck->finish();
        unless ($new_epobjectid) {
            #print "PROBLEM: $from_gid\n";
            next;
        }

        # move comments and votes and so forth to redirected gid destination
        for my $entry (["comments", "epobject_id",], 
                   ["anonvotes", "epobject_id",],
                   ["uservotes", "epobject_id",],
                   ["editqueue", "epobject_id_l",],
                   ["editqueue", "epobject_id_h",],
                   ) {
            my ($table, $field) = @$entry;
            my $epuse_comments = $dbh->prepare("select count(*) from epobject, hansard, $table
                where epobject.epobject_id = $table.$field and epobject.epobject_id = hansard.epobject_id and
                hansard.gid = ?");
            $epuse_comments->execute($from_gid);
            my $num_rows = $epuse_comments->fetchrow_array();
            $epuse_comments->finish();
            if ($num_rows > 0) {
                $hgetid->execute($from_gid);
                my $old_epobjectid = $hgetid->fetchrow_array();
                $hgetid->finish();

                print "gid $from_gid has $num_rows " . ($num_rows==1?'entry':'entries') . " in table $table, new gid $to_gid\n" unless $cronquiet;
                update_eid($table, $field, $old_epobjectid, $new_epobjectid);
             }
        }

        # Maintain video bits
        if ($matchtype eq 'missing') {
            $dbh->do('update video_timestamps set deleted=2 where gid=?', {}, $from_gid);
        } else {
            $dbh->do('update video_timestamps set gid=? where gid=?', {}, $to_gid, $from_gid);
        }
        my $video_update = $dbh->selectrow_array('select video_status from hansard where gid=?', {}, $from_gid);
        $dbh->do('update hansard set video_status=? where gid=?', {}, $video_update, $to_gid)
            if defined $video_update;

        # delete the now obsolete "from record" (which is replaced by its "to record")
        my $c = $hdeletegid->execute($from_gid);
        if ($c > 0) {
            print "deleted $from_gid which is now redirected to $to_gid\n" unless $cronquiet;
            print FP "$from_gid\n";
        }
        $hdeletegid->finish();
     }
     close FP;
}

sub update_eid {
    my ($table, $field, $old_epobjectid, $new_epobjectid) = @_;
    print "updating epobject id from $old_epobjectid => $new_epobjectid\n" unless $cronquiet;
    if ($table eq 'anonvotes') {
        my $epalready = $dbh->prepare("select epobject_id,yes_votes,no_votes from anonvotes where epobject_id=?");
        $epalready->execute($new_epobjectid);
        my @arr = $epalready->fetchrow_array();
        if ($arr[0]) {
            my $epdelete = $dbh->prepare('delete from anonvotes where epobject_id=?');
            $epdelete->execute($new_epobjectid);
            $epdelete->finish();
            my $epuse_updateid = $dbh->prepare("update anonvotes set yes_votes=yes_votes+$arr[1],
                no_votes=no_votes+$arr[2], epobject_id=? where epobject_id = ?");
            $epuse_updateid->execute($new_epobjectid, $old_epobjectid);
            $epuse_updateid->finish();
            return;
        }
    }
    my $epuse_updateid = $dbh->prepare("update $table set $field = ? where $field = ?");
    $epuse_updateid->execute($new_epobjectid, $old_epobjectid);
    $epuse_updateid->finish();
}


sub db_addpair
{
    my $epparams = shift;
    my $hparams = shift;
    my $gid = $$hparams[0];
    my $major = $$hparams[3];

    $ignorehistorygids{$gid} = 1;
       
    # Depending on what mode we're in
    if ($tallygidsmode) {
        die "Got gid $gid twice in XML file" if (defined $gids{$gid});
        $gids{$gid} = 1;
        $tallygidsmodedummycount++;
        return $tallygidsmodedummycount;
    }

    # Delete any redirect of this, should there be one
    $grdeletegid->execute($gid);
    $grdeletegid->finish();

    # See if we already have a hansard object with this global identifier (gid)
    my $q = $hcheck->execute($gid);
    die "More than one existing hansard object of same gid " . $gid if ($q > 1);

    if ($q == 1)
    {
        my @hvals = $hcheck->fetchrow_array();
        $hcheck->finish();
        my $epid = shift @hvals;
        if ($hvals[9] gt $hparams->[9]) { # the hdate column
            print "not updating hansard object $gid, db date of $hvals[9] greater than $hparams->[9]\n";
            return $epid;
        }

        # Check matching epobject exists
        my $q = $epcheck->execute($epid);
        my @epvals = $epcheck->fetchrow_array();
        $epcheck->finish();
        die "More than one existing epobject of same id " . $epid if ($q > 1);
        if ($q != 1)
        {
            print "strange, missing epobject $epid for gid $gid - part of db unexpectedly missing\n";
            print "deleting hansard object $gid and rebuilding\n";
            my $delcount = $hdelete->execute($gid, $epid);
            $hdelete->finish();
            die "Deleted " . $delcount . " rows when expected to delete one for " . $gid if $delcount != 1;
        } else {
            # Check to see if the existing hansard object and new one are the same
            if (!compare_arrays(\@hvals, $hparams))
            {
                # They differ - update the existing hansard object
                die "Sizes incompatible when comparing hansard objects (in " . $gid . ")" if $#hvals != $#$hparams;
                if (!$quiet) {
                    print "updating hansard object " . $gid . ", changing: ";
                    print describe_compare_arrays(\@hvals, $hparams) . "\n";
                }
                $hupdate->execute(@$hparams, $epid, $gid);
                $hupdate->finish();
            }

            # Check epobject is also the same
            if (!compare_arrays(\@epvals, $epparams))
            {
                # They differ - update the existing epobject
                die "Sizes incompatible when comparing epobjects (in " . $gid . ")" if $#epvals != $#$epparams;;
                if (!$quiet) {
                    print "updating epobject epid " . $epid . " for gid " . $gid . "\n";
                }
                $epupdate->execute(@$epparams, $epid);
                $epupdate->finish();
            }

            # Happy new and old objects are the same
            #print "existing object " . $gid . " ignored\n";
            return $epid;
        }
    }
    $hcheck->finish();
    
    $epadd->execute(@$epparams);
    my $epid = last_id();
    $epadd->finish();
    $hadd->execute($epid, @$hparams);
    $hadd->finish();

    # print "added " . $gid . "\n";
    
    return $epid;
}

# Autoincrement id of last added item
sub last_id
{
    $lastid->execute();
    my @arr = $lastid->fetchrow_array();
    $lastid->finish();
    return $arr[0];
}

sub person_id {
    my ($item, $member_id_attr) = @_;
    my $person_id = $item->att('person_id') || $membertoperson{$item->att($member_id_attr) || ""} || 'unknown';
    $person_id =~ s/.*\///;
    $person_id = $personredirect{$person_id} || $person_id;
    return $person_id;
}

##########################################################################
# Written Answers

sub add_wrans_day
{
    my ($date) = @_;
    
    use vars qw($lordshead);
    my $twig = XML::Twig->new(twig_handlers => { 
            'ques' => sub { do_load_speech($_, 3, 1, $_->sprint(1)) },
            'reply' => sub { do_load_speech($_, 3, 2, $_->sprint(1)) },
            'minor-heading' => sub {
                my $subheading = $_;
                if ($lordshead==1) {
                    my $ohgid = $_->att('id');
                    $ohgid =~ s/\d+\.\d+$//;
                    my ($lett) = $ohgid =~ /\d\d\d\d-\d\d-\d\d(.)/;
                    for ('a'..$lett) {
                        next if $_ eq $lett;
                        (my $oldgid = $ohgid) =~ s/$lett\.$/$_\./;
                        $hdeletegid->execute($oldgid.'L') unless $tallygidsmode;
                    }
                    my $ohcolnum = $_->att('colnum');
                    my $ohurl = $_->att('url');
                    my $overhead = XML::Twig::Elt->new('major-heading',
                        {       id=>$ohgid . 'L',
                            colnum=> $ohcolnum,
                            url=> $ohurl,
                            nospeaker=>'true'
                        }, 'HOUSE OF LORDS');
                    do_load_heading($overhead, 3, strip_string($overhead->sprint(1)));
                    $lordshead = 2;
                }
                do_load_subheading($subheading, 3, strip_string($subheading->sprint(1)))
            },
            'major-heading' => sub { do_load_heading($_, 3, strip_string($_->sprint(1))) },
            'gidredirect' => sub { do_load_gidredirect($_, 3) },
            }, output_filter => $outputfilter );
    $curdate = $date;

    # find out what gids there are (using tallygidsmode)
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 1; %gids = (); %grdests = (); $tallygidsmodedummycount = 10;
    $lordshead = 0;
    parsefile_glob($twig, $parldata . "scrapedxml/wrans/answers" . $curdate. "*.xml");
    # On 2015-01-26 Lords switched to a system that does give department names, like the Commons
    $lordshead = 1 if $curdate lt '2015-01-26';
    parsefile_glob($twig, $parldata . "scrapedxml/lordswrans/lordswrans" . $curdate. "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 3");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 0; %gids = ();
    $lordshead = 0;
    parsefile_glob($twig, $parldata . "scrapedxml/wrans/answers" . $curdate. "*.xml");
    $lordshead = 1 if $curdate lt '2015-01-26';
    parsefile_glob($twig, $parldata . "scrapedxml/lordswrans/lordswrans" . $curdate. "*.xml");

    # and delete anything that has been redirected (moving comments etc)
    delete_redirected_gids($date, \%grdests);
     
    undef $twig;
}

##########################################################################
# Debates (as a stream of headings and paragraphs)

sub add_debates_day
{
    my ($date) = @_;
    my $twig = XML::Twig->new(twig_handlers => { 
        'speech' => sub { do_load_speech($_, 1, 0, $_->sprint(1)) },
        'minor-heading' => sub { do_load_subheading($_, 1, strip_string($_->sprint(1))) },
        'major-heading' => sub { load_debate_heading($_, 1) },
        'oral-heading' => sub { $inoralanswers = 1 },
        'division' => sub { load_debate_division($_, 1) },
        'gidredirect' => sub { do_load_gidredirect($_, 1) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    # find out what gids there are (using tallygidsmode)
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 1; %gids = (); %grdests = (); $tallygidsmodedummycount = 10;
    parsefile_glob($twig, $parldata . "scrapedxml/debates/debates" . $curdate. "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 1");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 0; %gids = ();
    parsefile_glob($twig, $parldata . "scrapedxml/debates/debates" . $curdate. "*.xml");

    # and delete anything that has been redirected (moving comments etc)
    delete_redirected_gids($date, \%grdests);

    undef $twig;
}

# load <major-heading> tags
sub load_debate_heading { 
    my ($speech, $major) = @_;
    # we merge together the Oral Answers to Questions major heading with the
    # major headings "under" it.
    my $text = strip_string($speech->sprint(1));
    if ($inoralanswers) {
        if ($wrans_major_headings !~ /,\Q$text\E,/) { # $text =~ m/[a-z]/ || $text eq 'BILL PRESENTED' || $text eq 'NEW MEMBER' || $text eq 'POINT OF ORDER') {
            $inoralanswers = 0;
        } else {
            # &#8212; is mdash (apparently some browsers don't know &mdash;)
            $text = "Oral Answers to Questions &#8212; " . fix_case($text);
        }
    }
    do_load_heading($speech, $major, $text);
}

# load <division> tags
sub load_debate_division {
    my ($division, $major) = @_;

    my $gid = $division->att('id');
    my $divdate = $division->att('divdate');
    my $divnumber = $division->att('divnumber');
    my $house = $major == 101 ? 'lords' : 'commons';
    my $division_id = "pw-$divdate-$divnumber-$house";

    my $text =
"<p class=\"divisionheading\">Division number $divnumber</p>
<p class=\"divisionbody\"><a href=\"http://www.publicwhip.org.uk/division.php?date=$divdate&amp;number=$divnumber";
    $text .= '&amp;house=lords' if $major == 101;
    $text .= "&amp;showall=yes#voters\">See full
list of votes</a> (From <a href=\"http://www.publicwhip.org.uk\">The Public Whip</a>)</p>";

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
        if ($vote_counts_by_pid{$_} > 1) {
            $totals->{both}++;
            $voteupdate->execute($_, $division_id, 'both');
        }
    }

    # Okay, now construct HTML and add to database
    foreach my $list (@lists) {
        my $side = $list->att('vote');
        $text .= "<h2>\u$side</h2> <ul class='division-list'>";
        my @names = $list->children($vote_tag); # attr ids vote (teller), text is name
        foreach my $vote (@names) {
            my $person_id = person_id($vote, 'id');
            my $vote_direction = $vote->att('vote');
            my $teller = $vote->att('teller');
            my $name = $vote->sprint(1);
            $name =~ s/ *\[Teller\]//; # In Lords
            $name =~ s/^(.*), (.*)$/$2 $1/;
            $name =~ s/^(rh|Mr|Sir|Ms|Mrs|Dr) //;
            $text .= "<li><a href='/mp/?p=$person_id'>$name</a>";
            $text .= '&nbsp;(teller)' if $teller;
            $text .= "</li>\n";

            if ($vote_counts_by_pid{$person_id} == 1) {
                my $stored_vote = $vote_direction =~ /^(aye|content)$/ ? 'aye' : 'no';
                $stored_vote = "tell$stored_vote" if $teller;
                $totals->{$stored_vote}++;
                $voteupdate->execute($person_id, $division_id, $stored_vote);
            }
        }
        $text .= "</ul>";
    }

    my $majority_vote = $totals->{aye} > $totals->{no} ? 'aye': 'no';
    my $title = division_title($divnumber);
    $divisionupdate->execute($division_id, $house, $title, $divdate, $divnumber, $gid,
        $totals->{aye}, $totals->{no}, $totals->{absent}, $totals->{both}, $majority_vote);

    do_load_speech($division, $major, 0, $text);
}

sub division_title {
    my $divnumber = shift;
    my $heading = join(" &#8212; ", $currmajor || (), $currminor || ());
    return $heading || "Division No. $divnumber";
}

##########################################################################
# Lords Debates (as a stream of headings and paragraphs)

sub add_lordsdebates_day
{
    my ($date) = @_;
    my $twig = XML::Twig->new(twig_handlers => { 
        'speech' => sub { do_load_speech($_, 101, 0, $_->sprint(1)) },
        'minor-heading' => sub { do_load_subheading($_, 101, strip_string($_->sprint(1))) },
        'major-heading' => sub { do_load_heading($_, 101, strip_string($_->sprint(1))) },
        'division' => sub { load_debate_division($_, 101) },
        'gidredirect' => sub { do_load_gidredirect($_, 101) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    # find out what gids there are (using tallygidsmode)
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 1; %gids = (); %grdests = (); $tallygidsmodedummycount = 10;
    parsefile_glob($twig, $parldata . "scrapedxml/lordspages/daylord" . $curdate. "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 101");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 0; %gids = ();
    parsefile_glob($twig, $parldata . "scrapedxml/lordspages/daylord" . $curdate. "*.xml");

    # and delete anything that has been redirected (moving comments etc)
    delete_redirected_gids($date, \%grdests);

    undef $twig;
}

##########################################################################
# Westminster Hall (as a stream of headings and paragraphs)

sub add_westminhall_day
{
    my ($date) = @_;
    my $twig = XML::Twig->new(twig_handlers => { 
        'speech' => sub { do_load_speech($_, 2, 0, $_->sprint(1)) },
        'minor-heading' => sub { do_load_subheading($_, 2, strip_string($_->sprint(1))) },
        'major-heading' => sub { load_debate_heading($_, 2) },
        'oral-heading' => sub { $inoralanswers = 1; },
        'division' => sub { die "Division in Westminter Hall, not handled yet!" },
        'gidredirect' => sub { do_load_gidredirect($_, 2) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    # find out what gids there are (using tallygidsmode)
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 1; %gids = (); %grdests = (); $tallygidsmodedummycount = 10;
    parsefile_glob($twig, $parldata . "scrapedxml/westminhall/westminster" . $curdate. "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 2");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 0; %gids = ();
    parsefile_glob($twig, $parldata . "scrapedxml/westminhall/westminster" . $curdate. "*.xml");

    # and delete anything that has been redirected (moving comments etc)
    delete_redirected_gids($date, \%grdests);

    undef $twig;
}

sub add_wms_day {
    my ($date) = @_;
    use vars qw($heading $subheading $overhead);
    my $twig = XML::Twig->new(twig_handlers => {
            'minor-heading' => sub { $subheading = $_; },
            'major-heading' => sub { $heading = $_; },
            'gidredirect' => sub { do_load_gidredirect($_, 4) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    # On 2015-01-26 Lords switched to a system that does give department names, like the Commons
    my $lordsfn = \&load_lords_wms_speech;
    $lordsfn = \&load_wms_speech if $curdate ge '2015-01-26';

    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 1; %gids = (); %grdests = (); $tallygidsmodedummycount = 10;
    $twig->setTwigHandler('speech', $lordsfn);
    $heading = ''; $subheading = '';
    parsefile_glob($twig, $parldata . "scrapedxml/lordswms/lordswms" . $curdate . "*.xml");
    $twig->setTwigHandler('speech', \&load_wms_speech);
    $heading = ''; $subheading = '';
    parsefile_glob($twig, $parldata . "scrapedxml/wms/ministerial" . $curdate . "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 4");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
    $tallygidsmode = 0; %gids = (); $overhead = undef;
    $twig->setTwigHandler('speech', $lordsfn);
    $heading = ''; $subheading = '';
    parsefile_glob($twig, $parldata . "scrapedxml/lordswms/lordswms" . $curdate . "*.xml");
    $twig->setTwigHandler('speech', \&load_wms_speech);
    $heading = ''; $subheading = '';
    parsefile_glob($twig, $parldata . "scrapedxml/wms/ministerial" . $curdate . "*.xml");

    # and delete anything that has been redirected
    delete_redirected_gids($date, \%grdests);

    undef $twig;
}

sub is_dupe {
    my $rthon = '(right[ ])? (hon(\.|ourable)[ ])?';
    my $statement = 'Statement';
    if ($curdate ge '2015-01-26') {
    $rthon = '((right|rt)[ ])? (hon(\.|ourable)?[ ])?';
    $statement = '(Statement|Announcement)';
    }
    return 1 if $_[0] =~ /
    My[ ]
    $rthon
    (and[ ])? (noble[ ])?
    friend\s*.*?[ ]
    (has[ ])? (today[ ])?
    (  (made|issued)[ ]the[ ]following[ ] (Written[ ])? (Ministerial[ ])? $statement
     | published[ ]a[ ]report
    )
    /ix;
    return 0;
}

sub load_wms_speech {
    my ($twig, $speech) = @_;
    my $text = $speech->sprint(1);
    return 1 if is_dupe($text);
    do_load_heading($heading, 4, strip_string($heading->sprint(1))) if $heading;
    do_load_subheading($subheading, 4, strip_string($subheading->sprint(1))) if $subheading;
    do_load_speech($speech, 4, 0, $text);
}
sub load_lords_wms_speech {
    my ($twig, $speech) = @_;
    my $text = $speech->sprint(1);
    return 1 if is_dupe($text);
    my $firsthead = $heading || $subheading;
    if (!$overhead && $firsthead) {
        my $ohgid = $firsthead->att('id');
        $ohgid =~ s/\d+\.\d+$//;
        my ($lett) = $ohgid =~ /\d\d\d\d-\d\d-\d\d(.)/;
        for ('a'..$lett) {
            next if $_ eq $lett;
            (my $oldgid = $ohgid) =~ s/$lett\.$/$_\./;
            $hdeletegid->execute($oldgid.'L') unless $tallygidsmode;
        }
        my $ohcolnum = $firsthead->att('colnum');
        my $ohurl = $firsthead->att('url');
        $overhead = XML::Twig::Elt->new('major-heading',
            {       id=>$ohgid . 'L',
                colnum=> $ohcolnum,
                url=> $ohurl,
                nospeaker=>'true'
            }, 'HOUSE OF LORDS');
        do_load_heading($overhead, 4, strip_string($overhead->sprint(1)));
    }
    do_load_subheading($firsthead, 4, strip_string($firsthead->sprint(1))) if $firsthead;
    do_load_speech($speech, 4, 0, $text);
}

##########################################################################
# Northern Ireland Assembly

sub add_ni_day {
    my ($date) = @_;
    my $twig = XML::Twig->new(twig_handlers => { 
        'speech' => sub {
            my $speech = $_;
            if (!$currsection && !$currsubsection) {
                my $overhead = XML::Twig::Elt->new('major-heading',
                    {       id=>'uk.org.publicwhip/ni/'.$date.'.0.0',
                        url=>'',
                        nospeaker=>'true'
                    }, 'Northern Ireland Assembly');
                do_load_heading($overhead, 5, strip_string($overhead->sprint(1)));
            }
            do_load_speech($speech, 5, 0, $speech->sprint(1))
        },
        'minor-heading' => sub { do_load_subheading($_, 5, strip_string($_->sprint(1))) },
        'oral-heading/major-heading' => sub { load_ni_heading($_, 1) },
        'major-heading' => sub { load_ni_heading($_, 0) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    # find out what gids there are (using tallygidsmode)
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0;
    $tallygidsmode = 1; %gids = (); $tallygidsmodedummycount = 10;
    parsefile_glob($twig, $parldata . "scrapedxml/ni/ni" . $curdate. "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 5");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0;
    $tallygidsmode = 0; %gids = ();
    parsefile_glob($twig, $parldata . "scrapedxml/ni/ni" . $curdate. "*.xml");

    undef $twig;
}

sub load_ni_heading { 
    my ($speech, $inoralanswers) = @_;
    my $text = strip_string($speech->sprint(1));
    if ($inoralanswers) {
        $text = "Oral Answers to Questions &#8212; " . fix_case($text);
    }
    do_load_heading($speech, 5, $text);
    return 0; # Do not chain handlers
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
        'speech'    => sub { do_load_speech($_, 7, 0, $_->sprint(1)) },
        'minor-heading' => sub { do_load_subheading($_, 7, strip_string($_->sprint(1))) },
        'major-heading' => sub { do_load_heading($_, 7, strip_string($_->sprint(1))) },
        'division' => sub { load_scotland_division($_) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    # find out what gids there are (using tallygidsmode)
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0;
    $tallygidsmode = 1; %gids = (); $tallygidsmodedummycount = 10;
    parsefile_glob($twig, $parldata . "scrapedxml/sp-new/meeting-of-the-parliament/" . $curdate. "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 7");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0;
    $tallygidsmode = 0; %gids = ();
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
    my %out;
    my %totals = (for => 0, against => 0, abstentions => 0);
    while ($text =~ m#<mspname id="uk\.org\.publicwhip/member/([^"]*)" vote="([^"]*)">(.*?)\s\(.*?</mspname>#g) {
        my ($member_id, $vote, $name) = ($1, $2, $3);
        my $person_id = $membertoperson{$member_id} || 'unknown';
        $person_id =~ s/.*\///;
        $person_id = $personredirect{$person_id} || $person_id;
        push @{$out{$vote}}, '<a href="/msp/?m=' . $member_id . '">' . $name . '</a>';
        $totals{$vote}++;
        $voteupdate->execute($person_id, $division_id, $scotland_vote_store{$vote});
    }
    while ($text =~ m#<mspname id="uk\.org\.publicwhip/person/([^"]*)" vote="([^"]*)">(.*?)\s\(.*?</mspname>#g) {
        my ($person_id, $vote, $name) = ($1, $2, $3);
        push @{$out{$vote}}, '<a href="/msp/?p=' . $person_id . '">' . $name . '</a>';
        $totals{$vote}++;
        $voteupdate->execute($person_id, $division_id, $scotland_vote_store{$vote});
    }
    $text = "<p class='divisionheading'>Division number $divnumber</p> <p class='divisionbody'>";
    foreach ('for','against','abstentions','spoiled votes') {
        next unless $out{$_};
        $text .= "<strong>\u$_:</strong> ";
        $text .= join(', ', @{$out{$_}});
        $text .= '<br />';
    }
    $text .= '</p>';

    my $majority_vote = $totals{for} > $totals{against} ? 'aye': 'no';
    my $title = division_title($divnumber);
    $divisionupdate->execute($division_id, 'scotland', $title, $curdate, $divnumber, $gid,
        $totals{for}, $totals{against}, $totals{abstentions}, 0, $majority_vote);
    do_load_speech($division, 7, 0, $text);
}

sub add_scotwrans_day {
    my ($date) = @_;
    my $twig = XML::Twig->new(twig_handlers => { 
        'ques' => sub { do_load_speech($_, 8, 1, $_->sprint(1)) },
        'reply' => sub { do_load_speech($_, 8, 2, $_->sprint(1)) },
        'minor-heading' => sub { do_load_heading($_, 8, strip_string($_->sprint(1))) },
        #'major-heading' => sub { do_load_heading($_, 8, strip_string($_->sprint(1))) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    # find out what gids there are (using tallygidsmode)
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0;
    $tallygidsmode = 1; %gids = (); $tallygidsmodedummycount = 10;
    parsefile_glob($twig, $parldata . "scrapedxml/sp-written/spwa" . $curdate. "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 8");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0;
    $tallygidsmode = 0; %gids = ();
    parsefile_glob($twig, $parldata . "scrapedxml/sp-written/spwa" . $curdate. "*.xml");

    undef $twig;
}

##########################################################################
# Standing/Public Bill Committees

sub add_bill {
    my ($bill, $elt) = @_;

    my $lords = $bill =~ s/\s*\[Lords\]//;
    my $url = $elt->att('url') || '';
    my $session = $elt->att('session') || '';

    if (!$session && $url =~ /(\d\d\d\d)-?(\d\d)/) {
        $session = "$1-$2";
    } elsif (!$session) {
        die "Couldn't get session out of $url, $bill, $date";
    }

    # Get bill ID
    my $bill_id;
    $bill_id = $dbh->selectrow_array('select id from bills where url=?', {}, $url)
        if $url;
    if (!$bill_id) {
        $bill_id = $dbh->selectrow_array('select id from bills where title=? and session=?', {}, $bill, $session);
    }
    if (!$bill_id) {
        $dbh->do('insert into bills (session, title, lords, url, standingprefix) values (?,?,?,?,"")',
            {}, $session, $bill, $lords, $url);
        $bill_id = last_id();
    }
    return $bill_id;
}

sub add_standing_title {
    my ($heading, $bill, $bill_id, @preheadingspeech) = @_;
    $heading->att('id') =~ /^.*\/(.*?_.*?_)/;
    my $prefix = $1;
    $dbh->do('update bills set standingprefix=? where id=?', {}, $prefix, $bill_id);
    do_load_heading($heading, 6, $bill, $bill_id);
    foreach (@preheadingspeech) {
        do_load_speech($_, 6, $bill_id, $_->sprint(1));
    }
}

sub add_standing_day {
    my ($date) = @_;
    use vars qw($bill $bill_id $majorheadingstate @preheadingspeech);
    $majorheadingstate = 0;
    my $twig = XML::Twig->new(twig_handlers => { 
        'bill' => sub {
            $bill = strip_string($_->att('title'));
            $bill_id = add_bill($bill, $_);
            $majorheadingstate = 1; # Got a <bill>
        },
        'committee' => sub {
            my @names = $_->descendants('mpname');
            foreach (@names) {
                my $chairman = ($_->parent()->tag() eq 'chairmen');
                my $attending = ($_->att('attending') eq 'true');
                my $person_id = person_id($_, 'memberid');
                $current_file =~ /_(\d\d-\d)_/;
                my $sitting = $1;
                if (my ($id, $curr_attending) = $dbh->selectrow_array('select id,attending from pbc_members where person_id=? and bill_id=?
                    and sitting=?', {}, $person_id, $bill_id, $sitting)) {
                    if ($curr_attending != $attending) {
                        $dbh->do('update pbc_members set attending=? where id=?', {},
                            $attending, $id);
                    }
                } else {
                    $dbh->do('insert into pbc_members (bill_id, sitting, person_id, attending, chairman) values
                        (?, ?, ?, ?, ?)', {}, $bill_id, $sitting, $person_id, $attending, $chairman);
                }
            }
        },
        'major-heading' => sub {
            return if defined $ignorehistorygids{$_->att('id')};

            my $commhead = $_->sprint(1) =~ /(Standing Committee [A-H]|Special Standing Committee|Second Reading Committee)\s*$/;
            if ($_->sprint(1) =~ /^\s*Public Bill Commit?tee\s*$/) {
                # All PBCs have a <bill>
                add_standing_title($_, $bill, $bill_id, @preheadingspeech);
                $majorheadingstate = 9; # No more headings
            } elsif ($majorheadingstate==1 && $commhead) {
                # A <bill>, an old SC heading, the bill title will come again
                add_standing_title($_, $bill, $bill_id, @preheadingspeech);
                $majorheadingstate = 3;
            } elsif ($majorheadingstate==3) {
                # Ignore this heading of the bill title
                $majorheadingstate = 9;
            } elsif ($commhead) {
                # No <bill>, we're going to get another major-heading with the bill title in it...
                $majorheadingstate = 2;
            } elsif ($majorheadingstate==2) {
                $bill = strip_string($_->sprint(1));
                $bill_id = add_bill($bill, $_);
                add_standing_title($_, $bill, $bill_id, @preheadingspeech);
                $majorheadingstate = 9;
            } elsif ($majorheadingstate==0 || $majorheadingstate==1) {
                die "Odd first major heading: " . $_->sprint(1);
            } else {
                # Any major heading other than the ones above I'm assuming is part of an amendment
                # So load as a normal speech!
                do_load_speech($_, 6, $bill_id, $_->sprint(1));
            }
        },
        'speech' => sub {
            if ($currsection==0) {
                push @preheadingspeech, $_;
            } else {
                do_load_speech($_, 6, $bill_id, $_->sprint(1))
            }
        },
        'publicwhip' => sub {
            # Clear variables for next file
            $majorheadingstate = 0; $bill = ''; $bill_id = 0;
            @preheadingspeech = ();
        },
        'minor-heading' => sub { do_load_subheading($_, 6, strip_string($_->sprint(1)), $bill_id) },
        'divisioncount' => sub { load_standing_division($_, $bill_id) },
        'gidredirect' => sub { do_load_gidredirect($_, 6) },
        }, output_filter => $outputfilter );
    $curdate = $date;

    # find out what gids there are (using tallygidsmode)
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0;
    $tallygidsmode = 1; %gids = (); %grdests = (); $tallygidsmodedummycount = 10;
    parsefile_glob($twig, $parldata . "scrapedxml/standing/standing*_*_*_" . $curdate. "*.xml");
    # see if there are deleted gids
    my @gids = keys %gids;
    check_extra_gids($date, \@gids, "major = 6");

    # make the modifications
    $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0;
    $tallygidsmode = 0; %gids = ();
    parsefile_glob($twig, $parldata . "scrapedxml/standing/standing*_*_*_" . $curdate. "*.xml");

    # and delete anything that has been redirected (moving comments etc)
    delete_redirected_gids($date, \%grdests);

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
    my %out = ( aye => '', no => '' );
    foreach (@names) {
        my $person_id = person_id($_, 'memberid');
        my $name = $_->att('membername');
        my $v = $_->att('vote');
        $out{$v} .= '<a href="/mp/?p=' . $person_id . '">' . $name . '</a>, ';
        $voteupdate->execute($person_id, $division_id, $v);
    }
    $out{aye} =~ s/, $//;
    $out{no} =~ s/, $//;
    my $text = "<p class=\"divisionheading\">Division number $divnumber - $ayes yes, $noes no</p>
<p class=\"divisionbody_yes\">Voting yes: $out{aye}</p>
<p class=\"divisionbody_no\">Voting no: $out{no}</p>
";
    my $majority_vote = $ayes > $noes ? 'aye': 'no';
    my $title = division_title($divnumber);
    $divisionupdate->execute($division_id, 'pbc', $title, $curdate, $divnumber, $gid,
        $ayes, $noes, 0, 0, $majority_vote);
    do_load_speech($division, 6, $id, $text);
}

sub loadspq {
    my ($twig, $question) = @_;
    my %typemap = (
        'business-today', 1,
        'business-oral', 2,
        'business-written', 3,
        'answer', 4,
        'holding', 5,
        'oral-asked-in-official-report', 6,
        'referenced-in-question-text', 7 );

    my $gid = $question->att('gid');
    if (!$quiet) {
        print "Scottish Parliament question ID $gid\n";
    }

    my @mentions = $question->children();
    foreach my $mention (@mentions) {
        my $mentiontype = $typemap{$mention->att('type')};
        my $mentionname = $mention->att('type');
        unless ($mentiontype) {
            die "Unknown mention type ($mentiontype) found.";
        }
        my $mentiondate = $mention->att('date');
        my $doit = (!$mentiondate) || $all || $recent || ($datefrom le $mentiondate && $mentiondate le $dateto);
        print " ($mentionname) " unless $quiet;
        if (!$doit) {
            print " skipping\n" unless $quiet;
            next;
        }
        my $mentionurl = $mention->att('url');
        my $mentiongid;
        my $rows;
# Need to pick out a few attributes in some cases:
        if ($mentiontype == 4) {
            $mentiongid = $mention->att('spwrans');
        } elsif ($mentiontype == 6) {
            $mentiongid = $mention->att('orgid');
        } elsif ($mentiontype == 7) {
            $mentiongid = $mention->att('referrer');
        }

        my $preload_hash_key;
        if (%scotqspreloaded) {
            my @row = ($gid,$mentiontype,$mentiondate,$mentionurl,$mentiongid);
            $preload_hash_key = join('|', map { defined $_ ? $_ : '' } @row );

            if ($scotqspreloaded{$preload_hash_key}) {
                $rows = 1;
            } else {
                $rows = 0;
            }
        } else {
            if ($mentiontype >= 1 && $mentiontype <= 3) { # 'business-*'
                $rows = $scotqbusinessexist->execute($gid,$mentiontype,$mentiondate,$mentionurl);
                if ($rows > 1) {
                    die "Multiple rows matched $gid, $mentiontype, $mentiondate, $mentionurl";
                }
                my @row = $scotqbusinessexist->fetchrow_array();
                $scotqbusinessexist->finish();
            } elsif ($mentiontype == 4) { # 'answer'
                $rows = $scotqdategidexist->execute($gid,$mentiontype,$mentiondate,$mentiongid);
                if ($rows > 1) {
                    die "Multiple rows matched $gid, $mentiontype, $mentiondate, $mentiongid";
                }
                my @row = $scotqdategidexist->fetchrow_array();
                $scotqdategidexist->finish();
            } elsif ($mentiontype == 5) { # 'holding'
                $rows = $scotqholdingexist->execute($gid,$mentiontype,$mentiondate);
                if ($rows > 1) {
                    die "Multiple rows matched $gid, $mentiontype, $mentiondate";
                }
                my @row = $scotqholdingexist->fetchrow_array();
                $scotqholdingexist->finish();
            } elsif ($mentiontype == 6) { # 'oral-asked-in-official-report'
                $rows = $scotqdategidexist->execute($gid,$mentiontype,$mentiondate,$mentiongid);
                if ($rows > 1) {
                    die "Multiple rows matched $gid, $mentiontype, $mentiondate, $mentiongid";
                }
                my @row = $scotqdategidexist->fetchrow_array();
                $scotqdategidexist->finish();
            } elsif ($mentiontype == 7) { # 'referenced-in-question-text'
                $rows = $scotqreferenceexist->execute($gid,$mentiontype,$mentiongid);
                if ($rows > 1) {
                    die "Multiple rows matched $gid, $mentiontype, $mentiongid";
                }
                my @row = $scotqreferenceexist->fetchrow_array();
                $scotqreferenceexist->finish();
            }
        }

        if( $rows == 1 ) {
            if (!$quiet) { print "already present\n"; }
        } else {
            if (!$quiet) { print "inserting\n"; }
            $scotqadd->execute($gid,$mentiontype,$mentiondate,$mentionurl,$mentiongid);
            $scotqadd->finish();
            if (%scotqspreloaded) {
                $scotqspreloaded{$preload_hash_key} = 1;
            }
        }
    }
    $twig->purge();
}

sub canon_time {
    my $t = shift;
    $t = "$t";
    $t = substr($t, 1) if $t =~ /^0\d\d:/;
    $t .= ":00" if $t =~ /^\d\d?:\d\d$/; # Standing
    $t = "0$t" if $t =~ /^\d:/; # Standing
    return $t;
}

##########################################################################
# Handlers for speech/heading elements which are in debates, wrans and
# westminhall

sub do_load_speech
{
    my ($speech, $major, $minor, $text) = @_;

    my $id = $speech->att('id');
    my $colnum = $speech->att('colnum');
    $colnum =~ s/[^\d]//g if $colnum;

    my $len = length($speech->sprint(1));
    return if ($len == 0);

    if (defined $ignorehistorygids{$id}) {
        # This happens to historical speeches which have already been
        # redirected, and aren't needed to be repeated
        #print "Ignoring historical " . $id . "\n";
        return;
    }

    die "speech without (sub)heading $id '$text'" if $currsection == 0 and $currsubsection == 0;

    $hpos++;
    my $htime = $speech->att('time');
    $htime = canon_time($htime) if defined $htime;
    my $url = $speech->att('url') || '';

    my $type;
    my $speaker = 0;
    my $pretext = "";
    if ($speech->att('person_id') || $speech->att('speakerid') || $speech->att('speakername')) {
        $type = 12;
        $speaker = person_id($speech, 'speakerid');
        if ($speaker eq "unknown") {
            $speaker = 0;
            my $encoded = HTML::Entities::encode_entities($speech->att('speakername'));
            $pretext = '<p class="unknownspeaker">' . $encoded . ':</p> ';
        }
    } elsif (defined $speech->att('divnumber')) {
        # division
        $type = 14;
    } else {
        # procedural
        $type = 13;
    }

    my @epparam = ($pretext . $text);
    my @hparam = ($id, $colnum, $type, $speaker, $major, $minor, $currsection, $currsubsection, $hpos, $curdate, $htime, $url);
    my $epid = db_addpair(\@epparam, \@hparam);
}

sub do_load_heading
{
    my ($speech, $major, $text, $minor) = @_;
    $minor ||= 0;
    #print "heading " . $text . "\n";

    if (defined $ignorehistorygids{$speech->att('id')}) {
        # This happens to historical headings which have already been
        # redirected, and aren't needed to be repeated
        #print "Ignoring historical " . $speech->att('id') . "\n";
        return;
    }

    $hpos++;
    my $htime = $speech->att('time');
    $htime = canon_time($htime) if defined $htime;
    my $url = $speech->att('url') || '';
    my $colnum = $speech->att('colnum');
    $colnum =~ s/[^\d]//g if $colnum;

    my $type = 10;
    my $speaker = 0;

    $text = fix_case($text);
    my @epparam = ($text);
    my @hparam = ($speech->att('id'), $colnum, $type, $speaker, $major, $minor, 0, 0, $hpos, $curdate, $htime, $url);
    my $epid = db_addpair(\@epparam, \@hparam);

    $currsubsection = $epid;
    $currsection = $epid;
    $currmajor = $text;
    $currminor = '';
}

sub do_load_subheading
{
    my ($speech, $major, $text, $minor) = @_;
    $minor ||= 0;
    #print "subheading " . $speech->att('id') . "\n";

    if (defined $ignorehistorygids{$speech->att('id')}) {
        # This happens to historical headings which have already been
        # redirected, and aren't needed to be repeated
        #print "Ignoring historical " . $speech->att('id') . "\n";
        return;
    }

    # if the current section heading is a promoted one, clear it as if
    # it weren't there (to stop this subsection heading going under it)
    if ($promotedheading == $currsection)
    {
        $currsection = 0;
    }
    # fawkes PHP scripts don't display minor headings without a major
    # heading before them.  so we make such minor headings into major
    # headings.  this only happens at the start of a file.
    if ($currsection == 0)
    {
        # print "subheading promoted to heading " . $speech->att('id') . " $text\n";
        do_load_heading($speech, $major, $text, $minor);
        # store so we promote other subheadings, rather than putting under this
        $promotedheading = $currsection;
        return;
    }

    $hpos++;
    my $htime = $speech->att('time');
    $htime = canon_time($htime) if defined $htime;
    my $url = $speech->att('url') || '';
    my $colnum = $speech->att('colnum');
    $colnum =~ s/[^\d]//g if $colnum;

    my $type = 11;
    my $speaker = 0;

    $text = fix_case($text);
    my @epparam = ($text);
    my @hparam = ($speech->att('id'), $colnum, $type, $speaker, $major, $minor, $currsection, 0, $hpos, $curdate, $htime, $url);
    my $epid = db_addpair(\@epparam, \@hparam);

    $currsubsection = $epid;
    $currminor = $text;
}

sub do_load_gidredirect
{
    my ($gidredirect, $major) = @_;

    my $oldgid = $gidredirect->att('oldgid'); 
    my $newgid = $gidredirect->att('newgid');
    my $matchtype = $gidredirect->att('matchtype');
    # if matchtype is multiplecover, let through >1 identical GIDs

    $ignorehistorygids{$oldgid} = 1;

    if ($tallygidsmode) {
        if ($matchtype ne 'multiplecover' && defined $gids{$oldgid} && $grdests{$oldgid}[0] ne $newgid) {
            die "Got gid $oldgid twice, with different destinations, in XML file";
        }
        $gids{$oldgid} = 1;
        $grdests{$oldgid} = [ $newgid, $matchtype ];
        return;
    } else {
        return if ($matchtype eq 'multiplecover' && defined $gids{$oldgid});
        $gids{$oldgid} = 1;
        return if ($matchtype eq 'removed');
    }
 
    $gradd->execute($oldgid, $newgid, $curdate, $major);
    $gradd->finish();
}

# TODO
# Check we don't have duplicates between two tables


