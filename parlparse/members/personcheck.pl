#! /usr/bin/perl -w -I ../../mysociety/perllib
use strict;

# Script which compares every pair of MPs together looking for ones which
# have similar names.  "Similar" here is using the mySociety name matching
# code, which includes nicknames, and removing initials.  

# This is useful for checking you have names correct, and that personsets.py
# is behaving correctly at matching MPs together.

use XML::Twig;
use mySociety::CouncilMatch;
use Data::Dumper;
my $nickfile = "/home/francis/devel/mysociety/services/mapit-dadem-loading/nicknames/nicknames.csv";

# Load person ids in
my $twig = new XML::Twig( output_filter => 'safe' );
$twig->parsefile("people.xml");
my $peopleroot = $twig->root;
my @people = $peopleroot->children('person');
my $membertoperson = {};
foreach my $person (@people) {
    my $personid = $person->{'att'}->{'id'};
    my @offices = $person->children('office');
    foreach my $office (@offices) {
        my $officeid = $office->{'att'}->{'id'};
        $membertoperson->{$officeid} = $personid;
    }
}
 
# Check every member against every other
$twig = new XML::Twig( output_filter => 'safe' );
$twig->parsefile("all-members.xml");
my $memberroot= $twig->root;
my @members = $memberroot->children('member');
my $c = 0;
foreach my $m1 (@members) {
    $c++;
    print STDERR "$c\n";
    foreach my $m2 (@members) {
        my $id1 = $m1->{'att'}->{'id'};
        my $id2 = $m2->{'att'}->{'id'};
        my $person1 = $membertoperson->{$id1};
        my $person2 = $membertoperson->{$id2};

        my $a = $m1->{'att'}->{'firstname'} . " " . $m1->{'att'}->{'lastname'};
        my $b = $m2->{'att'}->{'firstname'} . " " . $m2->{'att'}->{'lastname'};

        # Same name is already covered by personsets.py
        next if $a eq $b;

        my $a_canon = mySociety::CouncilMatch::canonicalise_person_name($a);
        my $b_canon = mySociety::CouncilMatch::canonicalise_person_name($b);

        if (mySociety::CouncilMatch::match_modulo_nickname($a_canon, $b_canon, $nickfile)) {
            print "$a, $b -- $id1, $id2 -- $person1, $person2\n";
        }
    }
}

#my $a = "Jeffrey R Langton";
#my $b = "Langton, Mr Geoff";


