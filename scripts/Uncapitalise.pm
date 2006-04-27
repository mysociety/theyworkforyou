
package Uncapitalise;

# To test, this one liner is useful:
# perl -MUncapitalise -e 'while (my $line = <STDIN>) { Uncapitalise::format($line); print $line; }'

my %canonical = map {lc($_)=>$_}
# List of canonical versions of certain terms.
# Terms which contain only capital letters and no vowels are assumed to be
# acronyms; thus, "DCMS" and "DTLR" and friends need not appear in this list.
qw {
	a an at as and are
	but by 
	ere
	for from
	in into is
	of on onto or over
	per
	the to that than
	until unto upon
	via
	with while whilst within without
    etc
    RAF MoD DoT HIV AIDS ADHD EU LGC BSE
};

sub format ($) {
	# do most stuff
    $_[0] = join('', map { exists($canonical{lc($_)}) ? $canonical{lc($_)} : (/[aeiouy]/i ? uc(substr($_, 0, 1)) . lc(substr($_, 1)) : uc($_)) } grep { $_ ne '' } split(/([()\-.,;\s\/\\\[\]]+)/, $_[0]));

	# capitalise first letter
	substr($_[0], 0, 1) = uc(substr($_[0], 0, 1));
	substr($_[0], -1, 1) = uc(substr($_[0], -1, 1)) if $_[0] =~ / \w$/;
}
