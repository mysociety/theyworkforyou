#!/usr/bin/perl

# lordinfo2xml.pl
# katherine.lawler@cantab.net

# Takes output of lordbiogs.pl and matches to personID
#
# Example usage:
# ./lordinfo2xml.pl notices.csv > lordbiogs.xml
#
# where notices.csv is output from lordbiogs.pl

# notices.csv has columns:
# 	surname,firstnames,letters,description,scraped_from,parser,

#use Text::CSV::Simple;
use Data::Dumper;

use warnings;
use strict;
use XML::Twig;

my $datafile_peerInfo = $ARGV[0];
if (!defined ($datafile_peerInfo)){ die 'Specify a lordbiogs csv file eg. output from lordbiogs.pl.'; }

open(FP, $datafile_peerInfo) or die $!;
my @data_peerInfo;
while (<FP>) {
	s/^\s*"//;
	s/"\s*$//;
	push @data_peerInfo, [ split /"\s*,\s*"/ ];
}
close FP;

my $personIDs = {};
my %lords;

my $twig = XML::Twig->new(
    twig_handlers => { 'lord' => \&loadlord, 'person' => \&loadperson },
    output_filter => 'safe'
);
$twig->parsefile("../people.xml");
$twig->parsefile("../peers-ucl.xml");

# Create hash of surname to array of personIDs.
# Surnames with multiple IDs are ambiguous and are never matched to.

my ($lordname, $surname, $this_personID, @names, $title);
my $headers_peerInfo = shift(@data_peerInfo);

#########
# Go through the peerInfo lines and match them to a personID via surname.
# If surname has multiple IDs, report them all
#
# Example output: a match for Walker might return the following
# (fictional example)
# 
#########
#
# <lord matchname="WALKER" reportedname="Mr Michael Walker">
# 	<biography>on the occassion of his retirement from whatever</biography>
# 	<scrapedfrom>http://www.pm.gov.uk/....</scrapedfrom>
#	<matches count="multiple">
#	    <match personid="13890" name="Lord Walker of Aldringham"/>
#	    <match personid="13117" name="Lord Walker of Gestingthorpe"/>
#	    <match personid="13110" name="Lord Walker of Worcester"/>
#	</matches>
# </lord>
#
#########

my $line_peerInfo;
my (@ids, $id, $ids_string);
my $fullname_string;
my $print_string;
my $field;
my ($matchname, $firstnames, $description,$letters);


print '<?xml version="1.0" encoding="ISO-8859-1"?>',"\n";
print "<lordbiogs>\n";

for $line_peerInfo (@data_peerInfo){
	$surname = $line_peerInfo->[0]; # First word
	$matchname = (split(/ /, uc($surname)))[0]; # match first word.
	$firstnames = $line_peerInfo->[1];
	$description = $line_peerInfo->[3];
	my $source = $line_peerInfo->[4];
	$description =~ s/&/&amp;/;
	$description =~ s/^\s//;
	$description =~ s/\s$//;
	$description =~ s/^((following|on) his retirement as|formerly|lately) //;
	$letters = $line_peerInfo->[2];
	$letters =~ s/^\s//;
	$firstnames =~ s/\(|\)//g;

	my $match = $personIDs->{$matchname};
	unless (defined $match) {
		print STDERR "No matches for $matchname: $firstnames $surname\n";
		next;
	}
	my %ids;
	foreach (@$match) {
		my $fore = $lords{$_}->att('forenames') || 'XXX';
		my $forefull = $lords{$_}->att('forenames_full') || 'XXX';
		$ids{$_} = 1 if $firstnames =~ /$fore|$forefull/;
	}
	@ids = keys %ids;
	@ids = @$match if (!@ids && @$match==1);
	@ids = (13720) if $matchname eq 'TAYLOR' && $firstnames eq 'John Derek'; # Manual fix
	@ids = (10589) if $matchname eq 'TAYLOR' && $firstnames eq 'The Right Honourable Dr John David'; # Manual fix

	my $count = scalar(@ids);
	unless ($count == 1) {
		print STDERR "No or too many matches for $matchname: $firstnames $surname\n";
		next;
	}
	print '<personinfo id="uk.org.publicwhip/person/', $ids[0], '"';
	print ' lordbio="', $description, '"';
	print ' lordbio_from="', $source, '"';
	print "/>\n";
}
print "</lordbiogs>";

my %membertoperson;
sub loadlord {
	my ($twig, $member) = @_;
	my $id = $member->att('id');
        my $person_id = $membertoperson{$id};
	$id =~ s:uk.org.publicwhip/lord/::;
	$person_id =~ s:uk.org.publicwhip/person/::;
	if (my $surname = $member->att('surname')) {
		push @{$personIDs->{uc $surname}}, $person_id;
	}
	my $surname = uc $member->att('lordname');
	push @{$personIDs->{$surname}}, $person_id;
	$lords{$person_id} = $member;
}

sub loadperson {
    my ($twig, $person) = @_;
    my $curperson = $person->att('id');
    for (my $office = $person->first_child('office'); $office; $office = $office->next_sibling('office')) {
        $membertoperson{$office->att('id')} = $curperson;
    }
}

sub lord_name {
	my $m = shift;
	my $s = '';
	$s .= 'The ' unless $m->att('lordname');
	$s .= $m->att('title');
	$s .= ' ' . $m->att('lordname') if $m->att('lordname');
	$s .= ' of ' . $m->att('lordofname') if $m->att('lordofname');
	return $s;
}

