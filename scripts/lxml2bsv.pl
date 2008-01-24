#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;

# $Id: lxml2bsv.pl,v 1.2 2008-01-24 15:57:30 matthew Exp $
#
# Creates BSV file of some Lords data

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../../perllib";

use mySociety::Config;
mySociety::Config::set_file('../conf/general');
my $pwmembers = mySociety::Config::get('PWMEMBERS');

use DBI; 
use XML::Twig;
use File::Find;
use Getopt::Long;
use Data::Dumper;
use HTML::Entities;

use Uncapitalise;

use vars qw(%membertoperson);
my $twig = XML::Twig->new(twig_handlers => 
        { 'lord' => \&loadlord, 
          'person' => \&loadperson,
          }, 
        output_filter => 'safe' );
$twig->parsefile($pwmembers . "people.xml");
$twig->parsefile($pwmembers . "peers-ucl.xml");

sub loadlord {
	my ($twig, $member) = @_;
	my $id = $member->att('id');
        my $person_id = $membertoperson{$id};
	$id =~ s:uk.org.publicwhip/lord/::;
	$person_id =~ s:uk.org.publicwhip/person/::;
        die "Unknown house" if ($member->att('house') ne "lords");
        my $county = $member->att('county') || '';
        my $lordofname = $member->att('lordofname');
        my $lordofname_full = $member->att('lordofname_full') || '';
        if ($member->att('todate') eq '9999-12-31') {
                print "$person_id|$lordofname|$lordofname_full|$county\n";
        }
}

sub loadperson {
    my ($twig, $person) = @_;
    my $curperson = $person->att('id');
    for (my $office = $person->first_child('office'); $office;
        $office = $office->next_sibling('office')) {
        $membertoperson{$office->att('id')} = $curperson;
    }
}
