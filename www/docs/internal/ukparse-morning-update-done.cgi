#!/usr/bin/perl -w

use strict;
use FindBin;
use lib "$FindBin::Bin/../../../../perllib";

use mySociety::CGIFast;

while (my $q = new mySociety::CGIFast()) {
    open(FP, '|at NOW >/dev/null 2>/dev/null');
    print FP "/data/vhost/www.theyworkforyou.com/mysociety/twfy/scripts/morningupdate
/data/vhost/matthew.theyworkforyou.com/mysociety/twfy/scripts/morningupdate";
    close FP;
    print <<EOF;
Content-Type: text/plain

TheyWorkForYou morning update job scheduled.
EOF
    if ($q->path_info() eq "/0") {
        print "ukparse-morning-update-done.cgi: There were no scraper/parse errors\n";
    }
}

