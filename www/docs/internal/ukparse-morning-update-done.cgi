#!/usr/bin/perl -w

use strict;
use FindBin;
use lib "$FindBin::Bin/../../../../perllib";

use mySociety::CGIFast;
use mySociety::Config;
use mySociety::EmailUtil;
use mySociety::Email;

mySociety::Config::set_file("$FindBin::Bin/../../../conf/general");

my $path = mySociety::Config::get('BASEDIR') . '/../../scripts/morningupdate';

# Automatically reap the morningupdate children when they finish
$SIG{CHLD} = 'IGNORE';

while (my $q = new mySociety::CGIFast()) {
    my $pid = fork;
    if (not defined $pid) {
        print "Content-Type: text/plain\r\n\r\nFork failed";
    } elsif ($pid == 0) {
        my $errors = `$path`;
        if ($errors) {
            my $email = mySociety::Config::get('CONTACTEMAIL');
            my $body = mySociety::Email::construct_email({
                _body_ => $errors,
                Subject => 'TheyWorkForYou daily import failure',
                From => [ $email, 'TheyWorkForYou' ],
                To => $email,
            });
            mySociety::EmailUtil::send_email($body, $email, $email);
        }
    } else {
        print <<EOF;
Content-Type: text/plain

TheyWorkForYou morning update job scheduled.
EOF
        if ($q->path_info() eq "/0") {
            print "ukparse-morning-update-done.cgi: There were no scraper/parse errors\n";
        }
    }
}

