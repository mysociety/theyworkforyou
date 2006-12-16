#!/usr/bin/perl

use warnings;
use strict;
use LWP::Simple;

# Dec 2006 URL is http://www.pm.gov.uk/files/pdf/Acr3BB.pdf
my $url= shift || die "usage: $0 http://www.foo.com/path/to/file.pdf\n";

{
	my $all= &process(&read);
	$all= &cleanup_members($all);

	&output($all);
}

sub read {

if (0) {
	my $content= get($url) || die "couldn't fetch $url\n";
	open (OUT, ">/tmp/cab_cttes.pdf") || die "can't write to temp file /tmp/cab_cttes.pdf:$!";
	print OUT $content;
	close(OUT);
	`pdftotext -layout /tmp/cab_cttes.pdf`;
}
	my @lines;
	open (IN, "</tmp/cab_cttes.txt") || die "can't open /tmp/cab_cttes.txt $!";
	foreach my $l (<IN>) {
		chomp($l);
		next if $l =~ m#^\s*$#;
		push @lines, $l;
	}
	close (IN);
	return (@lines);
}


sub process {
	my @lines= @_;

	my $groups;#hash of all groups
	my $group; # name of group

	while (my $l = shift @lines) {
		last if $l=~ m#^\s+4\s*$#; # interesting stuff starts on page 4
	}

	while (my $line = shift @lines) {
		#print  "$line\n"  if ($line =~ m#^#);

		if ($line =~ m#^(.*)#) {
			$group=$1;
			my $subctte='';
			until ($lines[0]=~ m#^(?:SUB\-COMMITTEE|COMPOSITION)#) {
				#warn "in composition adding $line to $group\n\n\n============\n";
				$group.= " " . shift @lines;
			}
#warn "====$group----\n";
			if ($lines[0]=~ m#^(SUB\-COMMITTEE.*)#) {
				do {
					$subctte.= " " . shift @lines;
					#warn "in sub-ctte\n";
				} until ($lines[0] =~ m#COMPOSITION#);
			}
			if (defined $lines[0] and $lines[0]=~ m#COMPOSITION#) {
				shift @lines; #composition heading. ignore
				do {
					push @{$groups->{$group}->{$subctte}{'composition'}}, shift @lines;
					last if not defined $lines[0];
				} until ($lines[0] =~ m#^(?:TERMS OF REF|)#);
			}
			if (defined $lines[0] and $lines[0]=~ m#TERMS OF REF#) {
				shift @lines; # heading. ignore
				until ($lines[0]=~ m#^\s+\d+\s*$#) {
					#warn "in terms of use\n";
					$groups->{$group}->{$subctte}->{'termsofreference'}.= " " . shift @lines;
				}
			}
#use Data::Dumper; print Dumper($groups->{$group});
			#warn $line;

			#warn "outside\n";
		} elsif ($line =~ m#^\s+\d+\s*$#)  {
			# page number. ignore
		} else {
			warn $line;
		}
	#warn "outer most loop\n";
	}
#use Data::Dumper; print Dumper($groups);

	return ($groups);
}


sub cleanup_members {
	my $ref= shift;
	foreach my $ctte (keys %{$ref}) {
	   foreach my $subctte (keys %{$ref->{$ctte}}) {
		#print "\n$ctte: $subctte\n\t";
		my @members;

		# pass 1 - rules. does most of the checkable cleanup.
		foreach my $member (@{$ref->{$ctte}->{$subctte}->{'composition'}}) {
			if ($member =~ m#^[\(a-z]#) { #titles all start with a capital letter
				$members[$#members] .= " " . $member;
			} elsif ($member =~ m#^[^(]+\)#) { # tings which have a closing bracket by no opening are continuations
				$members[$#members] .= " " . $member;
			} elsif ($member =~ m#^\s+\d+\s*$#)  { # errant page numbers
			} elsif ($member =~ m#^\S+\s*$#)  { # positions all have more than one word
				$members[$#members] .= " " . $member;
			} else {
				push @members, $member;
			}
		}
		my @intermediate=@members;
		@members=();
		# pass 2. Look for keywords. Only doable after previous step

		my $attendance='';
		foreach my $member (@intermediate) {
			if ($member =~ m#^(?:Prime|Minster|Minister|Deputy|Secretary|Chief Whip|Chief Sec|Chancellor|Leader|The|Attorney|Other|Advocate|Paymaster|Solicitor|Parliamentary|When)#) {
				if ($member=~ m#^(?:The )?(.*) also has the right to attend#)  {
					$member = "[Right to Attend] $1";
				}
					
				push @members, $attendance.$member;
			} else {
				if ($member =~ m#In attendance#i) {
					$attendance= "[In Attendance] ";
				} elsif ($member =~ m#^Also has the right to attend$#) {
					$attendance= "[Right to Attend] ";
				} else {
					$members[$#members] .= " " . $member;
				}
				#$members[$#members] .= " " . $member;
			}
		}

		#print join "\n\t", @members;
		$ref->{$ctte}->{$subctte}->{'composition'}=\@members;
	   }
	}
	return ($ref);
}




sub output {
	my $ref = shift;


	foreach my $ctte (keys %{$ref}) {
		foreach my $subctte (keys %{$ref->{$ctte}}){  
			foreach my $member (@{$ref->{$ctte}->{$subctte}->{'composition'}}) {
				print "$member\n";
			}
		}
	}


}
