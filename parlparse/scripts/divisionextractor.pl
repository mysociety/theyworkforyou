#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;

# $Id: divisionextractor.pl,v 1.3 2004/07/21 01:33:32 fawkes Exp $
#
# Filters XML debates files, returning just the headings and divisions
 
use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use config; # see config.pm.incvs

use XML::Twig;
use File::Find;

use vars qw($curdate);

find(\&debateswanted, $config::pwdata . "scrapedxml/debates");
sub debateswanted {
        if (m/^debates(.*).xml$/) {
                extract_divisions_day($1, "", "debates", "debates");
        }
}

find(\&lordswanted, $config::pwdata . "scrapedxml/lordspages");
sub lordswanted {
        if (m/^daylord(.*).xml$/) {
                extract_divisions_day($1, "lords", "daylord", "lordspages");
        }
}


sub extract_divisions_day
{
        my ($date, $chamber, $filestub, $folderbit) = @_;
        $curdate = $date;

        my $fromfile = $config::pwdata . "scrapedxml/${folderbit}/$filestub" . $curdate. ".xml";
        my $tofile = $config::pwdata . "scrapedxml/${chamber}divisionsonly/${chamber}divisions" . $curdate. ".xml";

        my @fromstat = stat($fromfile);
        my @tostat = stat($tofile);
        die "from file $fromfile not there" if !@fromstat;
        if (@tostat) {
            # Compare ctimes
            if ($fromstat[10] < $tostat[10]) {
                return;
            }
        }
        # print "divisionextractor $curdate\n";
        #print "fromctime " . $fromstat[10]  . " toctime " . $tostat[10];
        
        my $twig = XML::Twig->new(twig_handlers => { 
                'speech' => \&trim_item,
                }, 
                output_filter => 'safe',
                pretty_print => 'indented'
                );
        $twig->parsefile($fromfile);
        open(OUT, ">" . $tofile . ".tmp") or die "failed to reopen stdout $tofile";
        print OUT $twig->sprint(1);
        close(OUT);
        rename $tofile . ".tmp", $tofile;
}

# Remove all <speech> items
sub trim_item
{ 
	my ($twig, $item) = @_;
        my $foundmotion = 0;
        for (my $para = $item->first_child('p'); $para;) 
        {
                my $nextpara = $para->next_sibling('p');
                if ( $para->att('pwmotiontext') ) {
                    $foundmotion = 1;
                } else {
                    $para->delete();
                }
                $para = $nextpara;
        }
        if (!$foundmotion) {
                $item->delete();
        }

}



