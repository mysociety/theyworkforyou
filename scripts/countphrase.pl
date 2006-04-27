#!/usr/bin/perl

# Extract phrases from XML that are Capitalised Inside Sentences, like that one.

# Use something like this:
# $ cat ~/pwdata/scrapedxml/debates/*.xml | ./countphrase.pl  >out
# $ cat out | sort | uniq -c | sort -n >phrases.txt

# To get output beginning thus:
#   4643 Prime Minister 
#   3658 Northern Ireland 
#   3452 The Government 
#   2439 United Kingdom 
#   2073 The Minister 
#   1949 European Union 
#   1885 United States 
#   1741 The Bill 
#   1660 Home Secretary 
#   1475 Foreign Secretary 
# ...

$currentphrase = "";
$c = 0;

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
    s/[.,-:;?&]/ punctuation /g;
    s/[^a-zA-Z ]//g;

    # Split into words
    split /\s/;

    # Loop through words
    foreach (@_)
    {
        # Strip whitespace from ends
        s/^\s+//;
        s/\s+$//;

        next if ($_ eq "");

		# Add a capitalised word to phrase
		if (m/[A-Z][a-z]*/ and $_ ne "I")
		{
			$currentphrase .= $_ . " ";
			$c++;
		}
		else
		{
			# End of set of capitalised words - if more than one word, write it out
			if ($currentphrase ne "" and $c > 1)
			{
				print $currentphrase . "\n";
			}
			# Reset ready for next capitalised phrase
			$currentphrase = "";
			$c = 0;
		}
    }
}

