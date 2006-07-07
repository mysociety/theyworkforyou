#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;

# $Id: xml2db.pl,v 1.6 2006-07-07 17:58:16 twfy-live Exp $
#
# Loads XML written answer, debate and member files into the fawkes database.
# 
# Magic numbers, and other properties of the destination schema
# are documented here:
#	http://parl.stand.org.uk/cgi-bin/moin.cgi/DataSchema
#	
# The XML files for Hansard objects come from the Public Whip parser:
#       http://scm.kforge.net/plugins/scmsvn/cgi-bin/viewcvs.cgi/trunk/parlparse/pyscraper/?root=ukparse
# And those for MPs are in (semi-)manually updated files here:
#       http://scm.kforge.net/plugins/scmsvn/cgi-bin/viewcvs.cgi/trunk/parlparse/members/?root=ukparse

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use config; # see config.pm.incvs

use DBI; 
use XML::Twig;
use File::Find;
use Getopt::Long;
use Data::Dumper;
use HTML::Entities;

use Uncapitalise;

# Memory profiling (used to have a problem with long runs of this script eating all system memory)
#use GTop ();
#my $gtop = GTop->new;
#my @attrs = qw(size vsize resident share rss);

# output_filter 'safe' uses entities &#nnnn; to encode characters, this is
# the easiest/most reliable way to get the encodings correct for content
# output with Twig's ->sprint (attributes are different, see use of
# encode_entities in the member stuff below)
my $outputfilter = 'safe';
#DBI->trace(1);

use vars qw($all $recent $date $datefrom $dateto $wrans $debates $westminhall $wms $lordsdebates $members $force $quiet $cronquiet $memtest);
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
                        "members" => \$members,
                        "force" => \$force,
                        "memtest" => \$memtest,
                        "quiet" => \$quiet,
                        "cronquiet" => \$cronquiet,
                        );

my $c = 0;
$c++ if $all;
$c++ if $recent;
$c++ if $date;
$c++ if ($datefrom || $dateto);
$c = 1 if $memtest;

if ((!$result) || ($c != 1) || (!$debates && !$wrans && !$westminhall && !$wms && !$members && !$memtest && !$lordsdebates))
{
print <<END;

Loads XML files from the parldata (pwdata) directory into the fawkes database.
The input files contain debates, written answers and so on, and were generated
by pyscraper from parlparse. This script synchronises the database to the
files, so existing entries with the same gid are updated preserving their
database id. 

--wrans - process Written Answers (C&L)
--debates - process Commons Debates
--members - process Members
--westminhall - process Westminster Hall
--wms - process Written Ministerial Statements (C&L)
--lordsdebates - process Lords Debates

--recent - acts incrementally, using xml2db-lastload files
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

if ($memtest)
{
        print "Mem test not enabled - need lib GTop and uncomment the code\n";
        #memory_test();
}

##########################################################################

use vars qw($hpos $curdate);
use vars qw($currsection $currsubsection $inoralanswers $promotedheading);
use vars qw(%gids %grdests %ignorehistorygids $tallygidsmode $tallygidsmodedummycount);
use vars qw(%membertoperson);
use vars qw();

use vars qw($debatesdir $wransdir $lordswransdir $westminhalldir $wmsdir $lordswmsdir $lordsdebatesdir);
$debatesdir = $config::pwdata . "scrapedxml/debates/";
$wransdir = $config::pwdata . "scrapedxml/wrans/";
$lordswransdir = $config::pwdata . "scrapedxml/lordswrans/";
$westminhalldir = $config::pwdata . "scrapedxml/westminhall/";
$wmsdir = $config::pwdata . "scrapedxml/wms/";
$lordswmsdir = $config::pwdata . "scrapedxml/lordswms/";
$lordsdebatesdir = $config::pwdata . "scrapedxml/lordspages/";

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
"LEADER OF THE HOUSE", "LEADER OF THE COUNCIL", "LORD CHANCELLOR", "LORD CHANCELLOR'S DEPARTMENT",
"LORD CHANCELLORS DEPARTMENT", "LORD CHANCELLOR'S DEPT", "LORD PRESIDENT OF THE COUNCIL", "MINISTER FOR WOMEN",
"WOMEN", "NATIONAL HERITAGE", "NORTHERN IRELAND", "OVERSEAS DEVELOPMENT ADMINISTRATION", "PRIME MINISTER",
"PRIVY COUNCIL", "PRIVY COUNCIL OFFICE", "PRESIDENT OF THE COUNCIL", "PUBLIC ACCOUNTS COMMISSION",
"PUBLIC ACCOUNTS COMMITTEE", "SOLICITOR-GENERAL", "SOLICITOR GENERAL", "SCOTLAND", "SOCIAL SECURITY", "TRANSPORT",
"TRANSPORT, LOCAL GOVERNMENT AND THE REGIONS", "TRADE AND INDUSTRY", "TREASURY", "WALES", "WORK AND PENSIONS"
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
        if (open FH, '<' . $xdirs->[0] . 'xml2db-lastload') {
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
                        my $xfile = $_;
                        my @stat = stat($xdir . $xfile);
                        my $use = ($stat[9] >= $xsince);
                        if (m/^$xname([0-9]{4}-[0-9]{2}-[0-9]{2})([a-z]*).xml$/) {
                                my $date_part = $1;
                                my $suffix_part = $2;
        
                                if ($xmaxtime[$i] < $stat[9]) {
                                        $xmaxfile = $xfile;
                                        $xmaxtime[$i] = $stat[9];
                                }

                                #print $xfile ." ".($use?"t":"f")." $xsince $stat[9]\n";
                                if ($all || ($use && $recent) || ($datefrom le $date_part && $date_part le $dateto)) {
                                        $process->{$date_part} = 1;
                                }
                        }
                };
                find({ wanted=>$xwanted, preprocess=>\&revsort }, $xdir);
        }

        # Go through dates, and load each one
        my $xname = join(',', @$xnames);
        foreach my $process_date (reverse sort keys %$process) {
                if (!$cronquiet) {
                        print "db loading $xname $process_date\n";
                }
                &$xdayfunc($process_date);
                # So we don't do it again
                my $xfile = "$process_date.xml";
                foreach my $xdir (@$xdirs) {
                        utime(($xsince - 1), ($xsince - 1), ($xdir.$xfile));
                }
        }

        # Store that we've done
        if ($recent) {
                my $xxmaxtime = 0;
                for (my $i=0; $i<@$xdirs; $i++) {
                        my $xdir = $xdirs->[$i];
                        (my $sxdir = $xdir) =~ s/lords(wrans|wms)/lordspages/;
                        # Find last update time
                        my @stat = stat($sxdir . "changedates.txt");
                        die "couldn't stat[9] $sxdir/changedates.txt" if (!$stat[9]);
                        die "xmaxtime[$i] not initialised" if (!$xmaxtime[$i]);
                        die "$stat[9] vs $xmaxtime[$i] : changedates.txt time isn't greater or equal than largest file" unless ($stat[9] >= $xmaxtime[$i]);
                        if ($xxmaxtime < $stat[9]) {
                                $xxmaxtime = $stat[9];
                        }
                        $xmaxtime[$i] = $stat[9];
                }
       
                if ($xxmaxtime != $xsince) {
                        # We use the current maxtime, so we run things still at that time again
                        # (the rsync from parlparse might have only got one of two files set in 
                        # the same second, and next time it might get the other)
                        #print "$xname since: $xsince new max $xmaxtime from changedates\n";
                        my $xdir = $xdirs->[0];
                        open FH, ">${xdir}xml2db-lastload" or die "couldn't open ${xdir}xml2db-lastload for writing";
                        print FH $xxmaxtime;
                        close FH;
                }
        }
}

# Process main data
process_type(["debates"], [$debatesdir], \&add_debates_day) if ($debates) ;
process_type(["answers", "lordswrans"], [$wransdir, $lordswransdir], \&add_wrans_day) if ($wrans);
process_type(["westminster"], [$westminhalldir], \&add_westminhall_day) if ($westminhall);
process_type(["ministerial", "lordswms"], [$wmsdir, $lordswmsdir], \&add_wms_day) if ($wms);
process_type(["daylord"], [$lordsdebatesdir], \&add_lordsdebates_day) if ($lordsdebates);

# Process members
if ($members) {
        add_mps_and_peers();
}

##########################################################################
# Utility

# Code from: perldoc -q 'How do I test whether two arrays or hashes are equal?'
# Why is this not built in?  This kind of thing makes me never want to use Perl again.
sub compare_arrays {
        my ($first, $second) = @_;
        return 0 unless @$first == @$second;
        for (my $i = 0; $i < @$first; $i++) {
                if (defined $first->[$i] or defined$second->[$i]) {
                        if (!defined $first->[$i] or !defined $second->[$i]) {
                                return 0;
                        }
                        $second->[$i] = '00:00:00' if ($second->[$i] eq 'unknown');
                        if ($first->[$i] ne $second->[$i]) {
                                return 0;
                        }
                }
        }
        return 1;
}

sub describe_compare_arrays {
        my ($first, $second) = @_;
        my $ret = "";
        if (@$first != @$second) {
                die "sizes differ in describe_compare_arrays";
        }
        for (my $i = 0; $i < @$first; $i++)
        {
                my $from = $first->[$i];
                my $to = $second->[$i];
                if (defined $from and (! defined $to))
                        { $ret .= "at $i value #$from# to <undef>. "; }
                elsif ((!defined $from) and defined $to)
                        { $ret .= "at $i value <undef> to #$to#. "; }
                elsif (defined $from and defined $to) {
                        if ("$from" ne "$to")
                                { $ret .= "at $i value #$from# to #$to#. "; }
                }
                elsif ((!defined $from) and (!defined $to)) {
                    # OK
                }
                else
                        { die "unknown defined status in describe_compare_arrays"; }
        }
        return $ret;
}


sub array_difference
{
        my $array1 = shift;
        my $array2 = shift;

        my @union = ();
        my @intersection = ();
        my @difference = ();

        my %count = ();
        foreach my $element (@$array1, @$array2) { $count{$element}++ }
        foreach my $element (keys %count) {
                push @union, $element;
                push @{ $count{$element} > 1 ? \@intersection : \@difference }, $element;
        }
        return \@difference;
}

sub strip_string {
        my $s = shift;
        $s =~ s/^\s+//;
        $s =~ s/\s+$//;
        return $s;
}

# Converts all capital parts of a heading to mixed case
sub fix_case
{
        $_ = shift;
#        print "b:" . $_ . "\n";

        # We work on each hyphen (mdash, &#8212;) separated section separately
        my @parts = split /&#8212;/;
        my @fixed_parts = map(&fix_case_part, @parts);
        $_ = join(" &#8212; ", @fixed_parts);

#        print "a:" . $_ . "\n";
        return $_;
}

sub fix_case_part
{
        # This mainly applies to departmental names for Oral Answers to Questions
#        print "fix_case_part " . $_ . "\n";

        s/\s+$//g; 
        s/^\s+//g;
        s/\s+/ /g;

        # if it is all capitals in Hansard
        # e.g. CABINET OFFICE
        if (m/^[^a-z]+(&amp;[^a-z]+)*$/)
        {
                die "undefined part title" if ! $_;
#                print "fixing case: $_\n";
                Uncapitalise::format($_);
#                print "fixed  case: $_\n";
        }
        die "not defined title part" if ! $_;

        return $_;
}

# Parse all the files which match the glob using twig.
sub parsefile_glob {
        my ($twig, $glob) = @_;
        my @files = glob($glob);
        %ignorehistorygids = ();
        foreach (@files) {
                #print "twigging: $_\n";
                $twig->parsefile($_);
        }
}

##########################################################################
# Database

my ($dbh, 
        $epadd, $epcheck, $epupdate,
        $hadd, $hcheck, $hupdate, $hdelete, $hdeletegid,
        $constituencyadd, $constituencydel, $memberadd, $memberexist, $membercheck, 
        $gradd, $grcheck, $grdeletegid,
        $lastid);

sub db_connect
{
        # Connect to database, and prepare queries
        $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });

        # epobject queries
        $epadd = $dbh->prepare("insert into epobject (title, body, type, created, modified)
                values (?, ?, ?, NOW(), NOW())");
        $epcheck = $dbh->prepare("select title, body, type from epobject where epobject_id = ?");
        $epupdate = $dbh->prepare("update epobject set title = ?, body = ?, type = ?, modified = NOW() where epobject_id = ?");

        # hansard object queries
        $hadd = $dbh->prepare("insert into hansard (epobject_id, gid, htype, speaker_id, major, minor, section_id, subsection_id, hpos, hdate, htime, source_url, created, modified)
                values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $hcheck = $dbh->prepare("select epobject_id, gid, htype, speaker_id, major, minor, section_id, subsection_id, hpos, hdate, htime, source_url from hansard where gid = ?");
        $hupdate = $dbh->prepare("update hansard set gid = ?, htype = ?, speaker_id = ?, major = ?, minor = ?, section_id = ?, subsection_id = ?, hpos = ?, hdate = ?, htime = ?, source_url = ?, modified = NOW()
                where epobject_id = ? and gid = ?");
        $hdelete = $dbh->prepare("delete from hansard where gid = ? and epobject_id = ?");
        $hdeletegid = $dbh->prepare("delete from hansard where gid = ?");

        # member (MP) queries
        $constituencydel = $dbh->prepare("delete from constituency");
        $constituencyadd = $dbh->prepare("insert into constituency
                (cons_id, name, main_name, from_date, to_date) values
                (?, ?, ?, ?, ?)");
        $memberadd = $dbh->prepare("replace into member (member_id, person_id, house, title, first_name, last_name,
                constituency, party, entered_house, left_house, entered_reason, left_reason) 
                values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $memberexist = $dbh->prepare("select member_id from member where member_id = ?");
        $membercheck = $dbh->prepare("select member_id from member where
                member_id = ? and person_id = ? and house = ? and title = ? and first_name = ? and last_name = ?
                and constituency = ? and party = ? and entered_house = ? and left_house = ?
                and entered_reason = ? and left_reason = ?"); 

        # gidredirect entries
        $gradd = $dbh->prepare("replace into gidredirect (gid_from, gid_to, hdate, major) values (?,?,?,?)");
        $grcheck = $dbh->prepare("select gid_from, hdate, major from gidredirect where gid_to = ?");
        $grdeletegid = $dbh->prepare("delete from gidredirect where gid_from = ?");

        # other queries
        $lastid = $dbh->prepare("select last_insert_id()");

        # Clear any half made previous attempts.
        delete_lonely_epobjects()
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
        my $q = $dbh->prepare('delete from epobject where epobject_id not in (select epobject_id from hansard)');
        my $rows = $q->execute();
        $q->finish();
        print "Lonely epobjects deleted: $rows\n" unless $cronquiet;
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
                                           ["trackbacks", "epobject_id",],
                                           ["editqueue", "epobject_id_l",],
                                           ["editqueue", "epobject_id_h",],
                                           ) {
                                my ($table, $field) = @$entry;
                                my $epuse_comments = $dbh->prepare("select count(*) from epobject, hansard, $table
                                        where epobject.epobject_id = $table.$field and epobject.epobject_id = hansard.epobject_id and
                                        hansard.gid = ?");
                                $epuse_comments->execute($gid);
                                my @arr = $epuse_comments->fetchrow_array();
                                $epuse_comments->finish();
                                if ($arr[0] > 0) {
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
        foreach my $from_gid (sort keys %$grdests) {
                my $to_gid = $grdests->{$from_gid};
                my $loop;
                do {
                        $loop = 0;
                        $q_redirect->execute($to_gid);
                        my @arr = $q_redirect->fetchrow_array();
                        if ($arr[0]) {
                                $loop = 1;
                                $to_gid = $arr[0];
                        }
                } while ($loop);
                $hcheck->execute($to_gid);
                my @arr = $hcheck->fetchrow_array();
                my $new_epobjectid = $arr[0];
                $hcheck->finish();
                unless ($new_epobjectid) {
#                        print "PROBLEM: $from_gid\n";
                        next;
                }

                # move comments and votes and so forth to redirected gid destination
                for my $entry (["comments", "epobject_id",], 
                                   ["anonvotes", "epobject_id",],
                                   ["uservotes", "epobject_id",],
                                   ["trackbacks", "epobject_id",],
                                   ["editqueue", "epobject_id_l",],
                                   ["editqueue", "epobject_id_h",],
                                   ) {
                        my ($table, $field) = @$entry;
                        my $epuse_comments = $dbh->prepare("select count(*) from epobject, hansard, $table
                                where epobject.epobject_id = $table.$field and epobject.epobject_id = hansard.epobject_id and
                                hansard.gid = ?");
                        $epuse_comments->execute($from_gid);
                        my @arr = $epuse_comments->fetchrow_array();
                        $epuse_comments->finish();
                        my $num_rows = $arr[0];
                        if ($num_rows > 0) {
                                my $hgetid = $dbh->prepare("select hansard.epobject_id from hansard 
                                        where hansard.gid = ?");
                                $hgetid->execute($from_gid);
                                @arr = $hgetid->fetchrow_array();
                                $hgetid->finish();
                                my $old_epobjectid = $arr[0];

                                print "gid $from_gid has $num_rows " . ($num_rows==1?'entry':'entries') . " in table $table, new gid $to_gid\n" unless $cronquiet;
                                print "updating epobject id from $old_epobjectid => $new_epobjectid\n" unless $cronquiet;

                                my $epuse_updateid = $dbh->prepare("update $table set $table.$field = ?
                                        where $table.$field = ?");
                                $epuse_updateid->execute($new_epobjectid, $old_epobjectid);
                                $epuse_updateid->finish();
                         }
                }

                # delete the now obsolete "from record" (which is replaced by its "to record")
                my $c = $hdeletegid->execute($from_gid);
                if ($c > 0) {
                        print "deleted $from_gid which is now redirected to $to_gid\n" unless $cronquiet;
                }
                $hdeletegid->finish();
         }
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
                }
                else
                {
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
                                        print "updating epobject epid " . $epid . " for gid " . $gid . ", changing: ";
                                        print describe_compare_arrays(\@epvals, $epparams) . "\n";
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
        $grcheck->finish();
        
	$epadd->execute(@$epparams);
	my $epid = last_id();
        $epadd->finish();
	$hadd->execute($epid, @$hparams);
        $hadd->finish();

        # print "added " . $gid . "\n";
        
        return $epid;
}

my %member_ids = ();
# Add member of parliament to database
sub db_memberadd
{
        my $id = $_[0];
        my $q = $memberexist->execute($id);
        $memberexist->finish();
        die "More than one existing member of same id " . $id if ($q > 1);

        if ($q == 1)
        {
                # Member already exists, check they are the same
                $q = $membercheck->execute(@_);
                $membercheck->finish();
                die "More than one exact matching member id " . $id if ($q > 1);
                if ($q == 0)
                {
                        print "Replacing existing member with new data for " . $id . "\n";
                        print "This is for your information only, just check it looks OK.\n";
                        print "\n";
                        print Dumper(\@_);
                }
        }
        else
        {
                print "Adding new member with identifier $id\n";
                print "This is for your information only, just check it looks OK.\n";
                print "\n";
                print Dumper(\@_);
        }

 	$memberadd->execute(@_);
        $memberadd->finish();
        $member_ids{$id} = 1;
        # print "added member " . $id . "\n";
}

# Autoincrement id of last added item
sub last_id
{
	$lastid->execute();
	my @arr = $lastid->fetchrow_array();
        $lastid->finish();
	return $arr[0];
}

##########################################################################
# Testing / debugging

=doc
sub mem_use_begin
{
        my $before = $gtop->proc_mem($$);
        return $before;
}

sub mem_use_end
{
        my $desc = shift;
        my $before = shift;
        my $after = $gtop->proc_mem($$);

        my %after = map {$_ => $after->$_()} @attrs;
        my %before = map { $_ => $before->$_() } @attrs;
        warn $desc . "\n";
        warn sprintf "%-10s : %-5s\n", $_,
                GTop::size_string($after{$_} - $before{$_}),
             for sort @attrs;
}

sub memory_test
{
        print "Memory test";

        my $memmark;
        for (my $i = 0; $i < 10; ++$i)
        {
                $memmark = mem_use_begin();
                        add_debates_day("2004-04-01");
                mem_use_end("add_debates_day", $memmark);
                my $qq = $dbh->do('delete from hansard where hdate="2004-04-01"');
                delete_lonely_epobjects();
        }
        db_disconnect();
        exit(0);
}

=cut


##########################################################################
# MPs and Peers, also constituencies, people

sub add_mps_and_peers {
        $dbh->do("delete from moffice");
        my $twig = XML::Twig->new(twig_handlers => 
                { 'constituency' => \&loadconstituency, 
                  'member' => \&loadmember, 
                  'lord' => \&loadlord, 
                  'person' => \&loadperson,
                  'moffice' => \&loadmoffice }, 
                output_filter => $outputfilter );
        $constituencydel->execute(); 
        $constituencydel->finish();
        $twig->parsefile($config::pwmembers . "constituencies.xml");
        $twig->parsefile($config::pwmembers . "people.xml");
        $twig->parsefile($config::pwmembers . "all-members.xml");
        $twig->parsefile($config::pwmembers . "peers-ucl.xml");
        $twig->parsefile($config::pwmembers . "ministers.xml");
        loadmoffices();
        check_member_ids();
        undef $twig;
}

sub check_member_ids {
        my $q = $dbh->prepare("select member_id from member"); 
        $q->execute();
        while (my @row = $q->fetchrow_array) {
                print "Member $row[0] in DB, not in XML\n" if (!$member_ids{$row[0]});
        }
}

my @moffices = ();
sub loadmoffices {
        # XXX: Surely the XML should join two consecutive offices together somewhere?!
        # Also, have to check all previous offices as offices are not consecutive in XML. <sigh>
        my $add = 1;
        @moffices = sort { $a->[3] cmp $b->[3] } @moffices;
        for (my $i=0; $i<@moffices; $i++) {
                for (my $j=0; $j<$i; $j++) {
                        next unless $moffices[$j];
                        if ($moffices[$i][7] eq $moffices[$j][7] && $moffices[$i][1] eq $moffices[$j][1]
                            && $moffices[$i][2] eq $moffices[$j][2] && $moffices[$i][3] eq $moffices[$j][5]
                            && $moffices[$i][4] eq $moffices[$j][6] ) {
                                $moffices[$j][5] = $moffices[$i][5];
                                $moffices[$j][6] = $moffices[$i][6];
                                delete $moffices[$i];
                                last;
                        }
                }
        }
        foreach my $row (@moffices) {
                next unless $row;
                my $sth = $dbh->do("insert into moffice (moffice_id, dept, position, from_date, to_date, person, source) values (?, ?, ?, ?, ?, ?, ?)", {}, 
                $row->[0], $row->[1], $row->[2], $row->[3], $row->[5], $row->[7], $row->[8]);
        }
}

# Add office
sub loadmoffice {
    my ($twig, $moff) = @_;

    my $mofficeid = $moff->att('id');
    $mofficeid =~ s#uk.org.publicwhip/moffice/##;
    my $mpid = $moff->att('matchid');
    return unless $mpid;

    my $person = $membertoperson{$moff->att('matchid')};
    die "mp " . $mpid . " " . $moff->att('name') . " has no person" if !defined($person);
    $person =~ s#uk.org.publicwhip/person/##;

    my $pos = $moff->att('position');
    if ($moff->att('responsibility')) {
        $pos .= ' (' . $moff->att('responsibility') . ')';
    }

    my $dept = $moff->att('dept');
    # Hack
    return if ($pos eq 'PPS (Rt Hon Peter Hain, Secretary of State)' && $dept eq 'Northern Ireland Office' && $person == 10518);
    return if ($pos eq 'PPS (Rt Hon Peter Hain, Secretary of State)' && $dept eq 'Office of the Secretary of State for Wales' && $person == 10458);

        # We encode entities as e.g. &Ouml;, as otherwise non-ASCII characters
        # get lost somewhere between Perl, the database and the browser.
        push @moffices, [$mofficeid, encode_entities($dept), encode_entities($pos), $moff->att('fromdate'),
                $moff->att('fromtime'), $moff->att('todate'), $moff->att('totime'), $person, $moff->att('source') ];
}

# Add constituency
sub loadconstituency
{
    my ($twig, $cons) = @_;

    my $consid = $cons->att('id');
    $consid =~ s#uk.org.publicwhip/cons/##;

    my $main_name = 1;
    for (my $name = $cons->first_child('name'); $name;
        $name = $name->next_sibling('name')) {

        # We encode entities as e.g. &Ouml;, as otherwise non-ASCII characters         
        # get lost somewhere between Perl, the database and the browser.
        $constituencyadd->execute(
            $consid,
            encode_entities($name->att('text')),             
            $main_name,
            $cons->att('fromdate'),
            $cons->att('todate'),
            );
        $constituencyadd->finish();
#        if ($main_name) {
#                print $name->att('text') . "\n";
#        }
        $main_name = 0;
    }
}

# Add members of parliament (from all-members.xml file)
sub loadmember {
	my ($twig, $member) = @_;

	my $id = $member->att('id');
        my $person_id = $membertoperson{$id};
	$id =~ s:uk.org.publicwhip/member/::;
	$person_id =~ s:uk.org.publicwhip/person/::;

	my $house = 1;
	if ($member->att('house') ne "commons") {
		die "Unknown house"; 
        }
        
        # We encode entities as e.g. &Ouml;, as otherwise non-ASCII characters
        # get lost somewhere between Perl, the database and the browser.
        # Just done for names (not constituency and party) as they are the
        # only place to have accents, and constituencies have & signs and
        # the postcode search matching system uses them.
	db_memberadd($id, 
                $person_id,
                $house, 
                encode_entities($member->att('title')),
                encode_entities($member->att('firstname')), 
                encode_entities($member->att('lastname')),
		encode_entities($member->att('constituency')), 
                $member->att('party'),
		$member->att('fromdate'), $member->att('todate'),
		$member->att('fromwhy'), $member->att('towhy'));
}

# Add members of parliament (from all-lords*.xml file)
sub loadlord {
	my ($twig, $member) = @_;

	my $id = $member->att('id');
        my $person_id = $membertoperson{$id};
	$id =~ s:uk.org.publicwhip/lord/::;
	$person_id =~ s:uk.org.publicwhip/person/::;
#        print "$id $person_id ".$member->att('title').' '.$member->att('lordname')."\n"; 

        my $house = 2;
        if ($member->att('house') ne "lords") {
		die "Unknown house"; 
        }
        
        my $fromdate = $member->att('fromdate');
        $fromdate = "$fromdate-01-01" if length($fromdate)==4;
        $fromdate = '0000-00-00' unless $fromdate;
        my $affiliation = $member->att('affiliation') || '';
        my $towhy = $member->att('towhy') || '';
        # We encode entities as e.g. &Ouml;, as otherwise non-ASCII characters
        # get lost somewhere between Perl, the database and the browser.
        # Just done for names (not constituency and party) as they are the
        # only place to have accents, and constituencies have & signs and
        # the postcode search matching system uses them.
	db_memberadd($id,
                $person_id,
                $house,
                encode_entities($member->att('title')),
                $member->att('forenames'),
                $member->att('lordname'), 
                encode_entities($member->att('lordofname')),
                $affiliation,
		$fromdate, $member->att('todate'),
		'', $towhy);
}

sub loadperson
{
    my ($twig, $person) = @_;
    my $curperson = $person->att('id');

    for (my $office = $person->first_child('office'); $office;
        $office = $office->next_sibling('office'))
    {
        $membertoperson{$office->att('id')} = $curperson;
    }
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
        parsefile_glob($twig, $config::pwdata . "scrapedxml/wrans/answers" . $curdate. "*.xml");
        $lordshead = 1;
        parsefile_glob($twig, $config::pwdata . "scrapedxml/lordswrans/lordswrans" . $curdate. "*.xml");
        # see if there are deleted gids
        my @gids = keys %gids;
        check_extra_gids($date, \@gids, "major = 3");

        # make the modifications
        $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
        $tallygidsmode = 0; %gids = ();
        $lordshead = 0;
        parsefile_glob($twig, $config::pwdata . "scrapedxml/wrans/answers" . $curdate. "*.xml");
        $lordshead = 1;
        parsefile_glob($twig, $config::pwdata . "scrapedxml/lordswrans/lordswrans" . $curdate. "*.xml");

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
        parsefile_glob($twig, $config::pwdata . "scrapedxml/debates/debates" . $curdate. "*.xml");
        # see if there are deleted gids
        my @gids = keys %gids;
        check_extra_gids($date, \@gids, "major = 1");

        # make the modifications
        $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
        $tallygidsmode = 0; %gids = ();
        parsefile_glob($twig, $config::pwdata . "scrapedxml/debates/debates" . $curdate. "*.xml");

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
                if ($wrans_major_headings !~ /,$text,/) { # $text =~ m/[a-z]/ || $text eq 'BILL PRESENTED' || $text eq 'NEW MEMBER' || $text eq 'POINT OF ORDER') {
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
	my $divdate = $division->att('divdate');
	my $divnumber = $division->att('divnumber');
        my $text = 
"<p class=\"divisionheading\">Division number $divnumber</p>
<p class=\"divisionbody\"><a href=\"http://www.publicwhip.org.uk/division.php?date=$divdate&amp;number=$divnumber";
        $text .= '&amp;house=lords' if $major == 101;
        $text .= "&amp;showall=yes#voters\">See full
list of votes</a> (From <a href=\"http://www.publicwhip.org.uk\">The Public Whip</a>)</p>";
        do_load_speech($division, $major, 0, $text);
}

##########################################################################
# Lords Debates (as a stream of headings and paragraphs)

sub add_lordsdebates_day
{
        my ($date) = @_;
        my $twig = XML::Twig->new(twig_handlers => { 
                'speech' => sub { do_load_speech($_, 101, 0, $_->sprint(1)) },
                'minor-heading' => sub { do_load_subheading($_, 101, strip_string($_->sprint(1))) },
#                'major-heading' => \&load_debate_heading,
                'division' => sub { load_debate_division($_, 101) },
                'gidredirect' => sub { do_load_gidredirect($_, 101) },
                }, output_filter => $outputfilter );
        $curdate = $date;

        # find out what gids there are (using tallygidsmode)
        $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
        $tallygidsmode = 1; %gids = (); %grdests = (); $tallygidsmodedummycount = 10;
        parsefile_glob($twig, $config::pwdata . "scrapedxml/lordspages/daylord" . $curdate. "*.xml");
        # see if there are deleted gids
        my @gids = keys %gids;
        check_extra_gids($date, \@gids, "major = 101");

        # make the modifications
        $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
        $tallygidsmode = 0; %gids = ();
        parsefile_glob($twig, $config::pwdata . "scrapedxml/lordspages/daylord" . $curdate. "*.xml");

        # and delete anything that has been redirected (moving comments etc)
        delete_redirected_gids($date, \%grdests);

        undef $twig;
}

# load <major-heading> tags
#sub load_debate_heading { 
#	my ($twig, $speech) = @_;
#
#        # we merge together the Oral Answers to Questions major heading with the
#        # major headings "under" it.
#        my $text = strip_string($speech->sprint(1));
#        if ($inoralanswers) {
#                if ($text =~ m/[a-z]/) {
#                        $inoralanswers = 0;
#                } else {
#                        # &#8212; is mdash (apparently some browsers don't know &mdash;)
#                        $text = "Oral Answers to Questions &#8212; " . fix_case($text);
#                }
#        }
#
#        do_load_heading($speech, 1, $text);
#}

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
        parsefile_glob($twig, $config::pwdata . "scrapedxml/westminhall/westminster" . $curdate. "*.xml");
        # see if there are deleted gids
        my @gids = keys %gids;
        check_extra_gids($date, \@gids, "major = 2");

        # make the modifications
        $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
        $tallygidsmode = 0; %gids = ();
        parsefile_glob($twig, $config::pwdata . "scrapedxml/westminhall/westminster" . $curdate. "*.xml");

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

        $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
        $tallygidsmode = 1; %gids = (); %grdests = (); $tallygidsmodedummycount = 10;
        $twig->setTwigHandler('speech', \&load_lords_wms_speech);
        $heading = ''; $subheading = '';
        parsefile_glob($twig, $config::pwdata . "scrapedxml/lordswms/lordswms" . $curdate . "*.xml");
        $twig->setTwigHandler('speech', \&load_wms_speech);
        $heading = ''; $subheading = '';
        parsefile_glob($twig, $config::pwdata . "scrapedxml/wms/ministerial" . $curdate . "*.xml");
        # see if there are deleted gids
        my @gids = keys %gids;
        check_extra_gids($date, \@gids, "major = 4");

        # make the modifications
        $hpos = 0; $currsection = 0; $currsubsection = 0; $promotedheading = 0; $inoralanswers = 0;
        $tallygidsmode = 0; %gids = (); $overhead = undef;
        $twig->setTwigHandler('speech', \&load_lords_wms_speech);
        $heading = ''; $subheading = '';
        parsefile_glob($twig, $config::pwdata . "scrapedxml/lordswms/lordswms" . $curdate . "*.xml");
        $twig->setTwigHandler('speech', \&load_wms_speech);
        $heading = ''; $subheading = '';
        parsefile_glob($twig, $config::pwdata . "scrapedxml/wms/ministerial" . $curdate . "*.xml");

        # and delete anything that has been redirected
        delete_redirected_gids($date, \%grdests);

        undef $twig;
}

sub is_dupe {
        return 1 if $_[0] =~ /My (?:right )?(?:hon(\.|ourable) )?(?:and )?(?:noble )?friend .*? (?:has )?(?:today )?(?:(?:made|issued) the following (?:Written )?Ministerial Statement|published a report)/i;
        return 0;
}
sub load_wms_speech {
        my ($twig, $speech) = @_;
        my $text = $speech->sprint(1);
        if (is_dupe($text)) {
                $text = "<p class=\"italic\">Probably duplicate of Commons Statement</p> $text";
                return 1;
        }
        do_load_heading($heading, 4, strip_string($heading->sprint(1))) if $heading;
        do_load_subheading($subheading, 4, strip_string($subheading->sprint(1))) if $subheading;
        do_load_speech($speech, 4, 0, $text);
}
sub load_lords_wms_speech {
        my ($twig, $speech) = @_;
        my $text = $speech->sprint(1);
        if (is_dupe($text)) {
                $text = "<p class=\"italic\">Probably duplicate of Commons Statement</p> $text";
                return 1;
        }
        my $firsthead = $heading || $subheading;
        if (!$overhead && $firsthead) {
                my $ohgid = $firsthead->att('id');
                $ohgid =~ s/\d+\.\d+$//;
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
# Handlers for speech/heading elements which are in debates, wrans and
# westminhall

sub do_load_speech
{
	my ($speech, $major, $minor, $text) = @_;

        my $id = $speech->att('id');

	my $len = length($speech->sprint(1));
	return if ($len == 0);

        if (defined $ignorehistorygids{$id}) {
                # This happens to historical speeches which have already been
                # redirected, and aren't needed to be repeated
                #print "Ignoring historical " . $id . "\n";
                return;
        }

        die "speech without (sub)heading " . $id if $currsection == 0 and $currsubsection == 0;

	$hpos++;
	my $htime = $speech->att('time');
        if (defined $htime) {
                $htime = "$htime";
                $htime = substr($htime, 1) if $htime =~ /^0\d\d:/;
        }
	my $url = $speech->att('url');

	my $type;
	my $speaker;
        my $pretext = "";
	if ($speech->att('speakerid'))
	{
		# with speaker
		$type = 12;
		$speaker = $speech->att('speakerid');
		$speaker =~ s:uk.org.publicwhip/(member|lord)/::;
                if ($speaker eq "unknown")
                {
                        $speaker = 0;
                        $pretext = '<p class="unknownspeaker">' . $speech->att('speakername') . ':</p> ';
                }
	}
	else
	{
		# procedural
		$type = 13;
		$speaker = 0;
	}

	my @epparam = ("", $pretext . $text, 1);
	my @hparam = ($id, $type, $speaker, $major, $minor, $currsection, $currsubsection, $hpos, $curdate, $htime, $url);
	my $epid = db_addpair(\@epparam, \@hparam);
}

sub do_load_heading
{
	my ($speech, $major, $text) = @_;
        my $minor = 0;
        #print "heading " . $text . "\n";

        if (defined $ignorehistorygids{$speech->att('id')}) {
                # This happens to historical headings which have already been
                # redirected, and aren't needed to be repeated
                #print "Ignoring historical " . $speech->att('id') . "\n";
                return;
        }

	$hpos++;
	my $htime = $speech->att('time');
        if (defined $htime) {
                $htime = "$htime";
                $htime = substr($htime, 1) if $htime =~ /^0\d\d:/;
        }
	my $url = $speech->att('url');

	my $type = 10;
	my $speaker = 0;
       
	my @epparam = ("", fix_case($text), 1);
	my @hparam = ($speech->att('id'), $type, $speaker, $major, $minor, 0, 0, $hpos, $curdate, $htime, $url);
	my $epid = db_addpair(\@epparam, \@hparam);

	$currsubsection = $epid;
	$currsection = $epid;
}

sub do_load_subheading
{
	my ($speech, $major, $text) = @_;
        my $minor = 0;
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
                do_load_heading($speech, $major, $text);
                # store so we promote other subheadings, rather than putting under this
                $promotedheading = $currsection;
                return;
        }

	$hpos++;
	my $htime = $speech->att('time');
        if (defined $htime) {
                $htime = "$htime";
                $htime = substr($htime, 1) if $htime =~ /^0\d\d:/;
        }
	my $url = $speech->att('url');

	my $type = 11;
	my $speaker = 0;

        my @epparam = ("", fix_case($text), 1);
        my @hparam = ($speech->att('id'), $type, $speaker, $major, $minor, $currsection, 0, $hpos, $curdate, $htime, $url);
	my $epid = db_addpair(\@epparam, \@hparam);

	$currsubsection = $epid;
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
                if ($matchtype ne 'multiplecover' && defined $gids{$oldgid} && $grdests{$oldgid} ne $newgid) {
                        die "Got gid $oldgid twice, with different destinations, in XML file";
                }
                $gids{$oldgid} = 1;
                $grdests{$oldgid} = $newgid;
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


