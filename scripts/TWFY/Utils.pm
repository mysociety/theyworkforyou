package TWFY::Utils;

use strict;
use warnings;
use Uncapitalise;

use parent qw(Exporter);
our @EXPORT = qw(compare_arrays describe_compare_arrays strip_string fix_case);

# Code from: perldoc -q 'How do I test whether two arrays or hashes are equal?'
# Why is this not built in?  This kind of thing makes me never want to use Perl again.
sub compare_arrays {
    my ($first, $second) = @_;
    return 0 unless @$first == @$second;
    for (my $i = 0; $i < @$first; $i++) {
        if (defined $first->[$i] or defined $second->[$i]) {
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
#    print "b:" . $_ . "\n";

    # We work on each hyphen (mdash, &#8212;) separated section separately
    my @parts = split /&#8212;/;
    my @fixed_parts = map(&fix_case_part, @parts);
    $_ = join(" &#8212; ", @fixed_parts);

#    print "a:" . $_ . "\n";
    return $_;
}

sub fix_case_part
{
    # This mainly applies to departmental names for Oral Answers to Questions
#    print "fix_case_part " . $_ . "\n";

    s/\s+$//g; 
    s/^\s+//g;
    s/\s+/ /g;

    # if it is all capitals in Hansard
    # e.g. CABINET OFFICE
    if (m/^[^a-z]+(&amp;[^a-z]+)*$/)
    {
        die "undefined part title" if ! $_;
#        print "fixing case: $_\n";
        Uncapitalise::format($_);
#        print "fixed  case: $_\n";
    }
    die "not defined title part" if ! $_;

    return $_;
}

1;
