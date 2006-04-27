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
#DBI->trace(2);

# Command line parser
$|=1;
my $dbfile=shift;
die "Specify Xapian database file as first parameter" if !$dbfile;
my $action=shift;

# Open MySQL
my $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });

# Open Xapian database
my $db=Search::Xapian::WritableDatabase->new($dbfile, Search::Xapian::DB_CREATE_OR_OPEN);

# Get data for items to update from MySQL MySQL
my $query = "select body, person_id, hdate, gid, major from tmpindextest";
my $q = $dbh->prepare($query);
$q->execute();

# Loop through all the rows from MySQL
my $parser=new HTML::Parser();
$parser->handler(text => \&text);
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
	my $area = $1; # wrans or debate
    if ($$row{'hdate'} ne $last_hdate || $area ne $last_area) {
        $last_hdate = $$row{'hdate'};
		$last_area = $area;
        print "xapian indexing $area $last_hdate\n";
    }
    #print "$gid $person_id $$row{'major'}\n";
    
    # Make new post for this item in Xapian
    $::doc=new Search::Xapian::Document();
    $::doc->set_data($gid);
    $::doc->add_term("speaker:" . $person_id);
    $::doc->add_term("major:" . $$row{'major'});
    $::doc->add_term($gid);
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
    print "\n";
}

print "undefining:\n";
undef $db;

# Does the actual adding (this is a handler for the HTML parser)
sub text {
    my $p=shift;
    my $text=shift;

    $text =~ s/&#\d+;/ /g;
    $text =~ s/[^A-Za-z0-9]/ /g;
    my @words = split /\s+/,$text;

    foreach my $word (@words) {
        next if $word eq '';
        next if $word =~ /^\d+$/;
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

