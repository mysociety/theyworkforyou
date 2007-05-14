# Syllable.pm
# Based upon Lingua::Rhyme and Lingua::EN::Syllable, but seeing
# as the former needs MySQL and the latter doesn't even work for "a"...

package Syllable;

require Exporter;
@ISA = qw/ Exporter /;
@EXPORT = qw/ syllable /;
@EXPORT_OK = qw/ @AddSyl @SubSyl /;
use vars qw/ $VERSION $REVISION @AddSyl @SubSyl /;
use strict;

use FindBin;

$VERSION = '0.251';
$REVISION = '$Id: Syllable.pm,v 1.2 2007-05-14 16:47:41 twfy-live Exp $ ';

my %lookup;
open(FP, "$FindBin::Bin/Syllable.txt") or die $!;
while (<FP>) {
	my @row = split;
	$lookup{$row[0]} = $row[1];
}
close FP;

# basic algortithm:
# each vowel-group indicates a syllable, except for:
#  final (silent) e
#  'ia' ind two syl 

# @AddSyl and @SubSyl list regexps to massage the basic count.
# Each match from @AddSyl adds 1 to the basic count, each @SubSyl match -1
# Keep in mind that when the regexps are checked, any final 'e' will have
# been removed, and all '\'' will have been removed.

@SubSyl = (
	'cial',
	'tia',
	'cius',
	'cious',
	'giu',              # belgium!
	'ion',
	'iou',
	'sia$',
	'.ely$',             # absolutely! (but not ely!)
);
@AddSyl = ( 
	'ia',
	'riet',
	'dien',
	'iu',
	'io',
	'ii',
	'[aeiouym]bl$',     # -Vble, plus -mble
	'[aeiou]{3}',       # agreeable
	'^mc',
	'ism$',             # -isms
	'([^aeiouy])\1l$',  # middle twiddle battle bottle, etc.
	'[^l]lien',         # alien, salient [1]
	'^coa[dglx].',      # [2]
	'[^gq]ua[^auieo]',  # i think this fixes more than it breaks
	'dnt$',           # couldn't
);

# (comments refer to titan's /usr/dict/words)
# [1] alien, salient, but not lien or ebbullient...
#     (those are the only 2 exceptions i found, there may be others)
# [2] exception for 7 words:
#     coadjutor coagulable coagulate coalesce coalescent coalition coaxial

sub syllable {
	my $word = shift;   

	return $lookup{uc $word} if $lookup{uc $word};

	my(@scrugg,$syl);
	$word =~ tr/A-Z/a-z/;
	$word =~ s/\'//g; # fold contractions.  not very effective.
	$word =~ s/e$//;
	@scrugg = split(/[^aeiouy]+/, $word); # '-' should perhaps be added?
	shift(@scrugg) unless ($scrugg[0]);
	$syl = 0;
	# special cases
	foreach (@SubSyl) {
		$syl-- if $word=~/$_/;
	}
	foreach (@AddSyl) {
		$syl++ if $word=~/$_/;
	}
	# $syl++ if length($word)==1;# 'x'
	# count vowel groupings
	$syl += scalar(@scrugg);
	$syl=1 if $syl==0; # got no vowels? ("the", "crwth")
	return $syl;
}

1;
