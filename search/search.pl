#!/usr/bin/perl -w

# Script for testing / development only.  Actual queries are done from PHP.
# Does simple searches of Xapian databases from the command line.

use strict;
use Carp;

use Search::Xapian qw(:standard);

my $dbfile=shift;
die "specify xapian db" if !$dbfile;

my $db=new Search::Xapian::Database($dbfile);
croak if !$db;

#$::Stemmer=new Search::Xapian::Stem('english');
my @search;
foreach my $a (@ARGV) {
	push @search,$a;
	#push @search,$::Stemmer->stem_word($a);
}

my $q=new Search::Xapian::Query(OP_PHRASE,@search);
croak if !$q;

print $q->get_description(), "\n";

my $enq=$db->enquire($q);
#$enq->set_collapse_key(3);
#$enq->set_sorting(10, 0, 0);
#$enq->set_bias(0, 86400);
$enq->set_collapse_key(4); # by subdebate
my $matches=$enq->get_mset(0,100000);

print "est: ",$matches->get_matches_estimated(),"\n";
print "size: ",$matches->size(),"\n";

for(my $match=$matches->begin() ; $match ne $matches->end() ; ++$match) {
    print $match->get_document()->get_data(),' -> ',$match->get_percent(),"% ";
	print $match->get_weight(),"w";
	print " \n";
}
