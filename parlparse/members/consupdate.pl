#! /usr/bin/perl -w
# $Id: consupdate.pl,v 1.1 2005/05/08 12:24:57 frabcus Exp $
#
# update the constituency XML file, has heuristics to match same names

# 2005 General Election boundary changes:
#   There were 72 Scottish constituencies before 2005.
#   56 new ones with new boundaries
#   3 new ones with same boundaries
#   So, 72 - 56 - 3 = 13 fewer constituencies
#   Total parliament went from 659 to 646 = 13 fewer MPs
# See: http://www.election.press.net/boundarychanges.html

use strict;
use XML::Twig;

# move_compass_to_start STRING
# Move compass directions (North, South, East, West) to start of string
# and to have that order.  Requires a lowercase string, and ignores
# spaces.
sub move_compass_to_start {
    my ($match) = @_;

    # Move compass points to start
    my $compass = "";
    foreach my $dir ("north", "south", "east", "west") {
        while ($match =~ m/($dir)/) {
            $match =~ s/^(.*)($dir)(.*)$/$1$3/;
            $compass .= "$dir";
        }
    }
    return $compass . $match;
}

# Canonicalise constituency name
sub canonicalise_commons_cons {
    $_ = shift;
    s/&amp;/and/;
    s/\&/and/;
    s/([^,]*), (.*)/$2 $1/;
    s/,//;
    s/-//g;
    s/St /St. /g;
    s/\s//g;
#    s/(North|South|East|West|Mid|Central) (.*)/$2 $1/;
#    s/((North|South) (East|West)) (.*)/$4 $1/;
#    s/(North|South|East|West|Mid|Central) (.+?)\b(.*)/$2 $1$3/;
    $_ = lc;
    $_ = move_compass_to_start($_);
    return $_;
}

# Load in existing constituencies
my $twig = new XML::Twig( output_filter => 'safe' );
$twig->parsefile("constituencies.xml");

# Index them by canonicalised constituency, and other things
my $root= $twig->root;
my @constituencies= $root->children('constituency');
my $canon_lookup = {}; # canonicalised name => cons id
my $already_got = {};  # full name => if already got in XML
my $twig_lookup = {};  # cons id => XML twig node
foreach my $constituency (@constituencies) {
    my $cons_id = $constituency->{'att'}->{'id'};
    foreach my $name ($constituency->children('name')) {
        my $cons_name = $name->{'att'}->{'text'};
        my $canon_name = canonicalise_commons_cons($cons_name);
        if (defined($canon_lookup->{$canon_name})) {
            if ($canon_lookup->{$canon_name} ne $cons_id) {
                die "Same canonincal constituency name with differing ids\n$canon_name, $cons_id, " . $canon_lookup->{$canon_name};
            }
        }
        $canon_lookup->{$canon_name} = $cons_id;
        $already_got->{$cons_name} = 1;
        $twig_lookup->{$cons_id} = $constituency;
    }
}

# Load in new Scottish constituency names
open(BOUND, '<../rawdata/boundary-changes2005.html');
my $new_scottish; # Canonical name => 1 if is a boundary changed scottish constituency
my $c = 0;
while (<BOUND>) {
    my ($pa_id, $pa_cons) = m/class="style2">(\d+) (.+)<br>/;
    next if !$pa_id;
    my $canon_pa_cons = canonicalise_commons_cons($pa_cons);
    #print $canon_pa_cons. "\n";
    $new_scottish->{$canon_pa_cons} = 1;
    $c++;
}
die if $c != 56;

# Make modifications
open(PARL, '<../rawdata/pa-constituencies2005.txt');
my $new_start_date = "2005-05-05";
my $new_end_date = "9999-12-31";
my $new_cons_id = 659;
my $new_already_got;
my $new_canon_lookup;
while (<PARL>) { 
    chomp; 
    s/\s+/ /g;
    s/^ //;
    s/ $//;
    s/ \(ex Speaker\)//;
    my ($pa_id, $pa_name) = m/^([^ ]+) (.+)$/;
    my $canon_pa_name = canonicalise_commons_cons($pa_name);

    if (defined($new_scottish->{$canon_pa_name})) {
        # New boundary scottish constituency
        my $cons_id = $new_canon_lookup->{$canon_pa_name};
        my $constituency_node;
        if (!defined($cons_id)) {
            # Create new node with new id for constituency
            $constituency_node = new XML::Twig::Elt('constituency');
            $new_cons_id++;
            $cons_id = "uk.org.publicwhip/cons/$new_cons_id";
            $constituency_node->{'att'}->{'id'} = $cons_id;
            $constituency_node->{'att'}->{'fromdate'} = $new_start_date;
            $constituency_node->{'att'}->{'todate'} = $new_end_date;
            $constituency_node->paste('last_child', $root);
            $new_canon_lookup->{$canon_pa_name} = $cons_id;
            $twig_lookup->{$cons_id} = $constituency_node;
        }  else {
            # Otherwise load in existing node
            $constituency_node = $twig_lookup->{$cons_id};
        }
        # If necessary, add new spelling to node
        if (!$new_already_got->{$pa_name}) {
            my $name_node = new XML::Twig::Elt('name');
            $name_node->{'att'}->{'text'} = $pa_name;
            $name_node->paste('last_child', $constituency_node);
            $new_already_got->{$pa_name} = 1;
        }
    }
    elsif ($already_got->{$pa_name}) {
        # Already have this exact spelling, so just update date
        my $cons_id = $canon_lookup->{$canon_pa_name};
        my $constituency_node = $twig_lookup->{$cons_id};
        $constituency_node->{'att'}->{'todate'} = $new_end_date;
        #print $constituency_node->{'att'}->{'id'} . "\n";
    } else {
        die "Failed to work out what to do with constituency: ".$pa_id.":".$pa_name.":".$canon_pa_name;
    }
}

# Output with modifications
$twig->set_pretty_print( 'indented');
$twig->print();

