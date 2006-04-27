#!/usr/bin/perl

# Count frequency of one, two and three word phrases in XML files

# Use something like this:
# $ cat ~/pwdata/scrapedxml/debates/2004-05*.xml | ./countphrase.pl  >out

use Data::Dumper;

$c = 0;
$counts = {};

while (<>)
{
    chomp;

    # Strip XML tags
    s/\<.+?\>/ /g;

    # Strip whitespace from ends, squash in middle
    s/^\s+//;
    s/\s+$//;
	s/\s+/ /;

    # Convert bad quotes of X
    s/\bX([A-Z])/ $1/;

    # Strip any other characters 
    s/[.,-:;?&]/ /g;
    s/[^a-zA-Z ]//g;

    # Split into words
    split /\s/;

	$lastword = "";
	$lastlastword = "";

    # Loop through words
    foreach (@_)
    {
        # Strip whitespace from ends
        s/^\s+//;
        s/\s+$//;

        next if ($_ eq "");

		$_ = lc $_;
		$counts->{$_}++;		

		if ($lastword ne "") {
			$counts->{"$lastword $_"}++;
			if ($lastlastword ne "") {
				$counts->{"$lastlastword $lastword $_"}++;
			}
		}

		$lastlastword = $lastword;
		$lastword = $_;
    }
}

@sorted = sort { $counts->{$a} <=> $counts->{$b} } keys %$counts;
foreach (@sorted) {
	print $_ . " " . $counts->{$_} . "\n";
}


