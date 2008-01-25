#! /usr/bin/perl -w
#
# One off because we didn't send any email for 2-3 weeks.
# 
# $Id: one-off-email-probs-users.pl,v 1.1 2008-01-25 19:57:31 twfy-live Exp $

exit; # Not to be used again!

use strict;

use DBI; 
use FindBin;
chdir $FindBin::Bin;
use lib $FindBin::Bin;
use lib "../../perllib";
use config; # see config.pm.incvs
use mySociety::Config;
use mySociety::Email;
use mySociety::EmailUtil;

mySociety::Config::set_file('ms-config');

my $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });

my $alerts = $dbh->selectall_arrayref("
	select user_id, email, concat(firstname,' ',lastname) as name, registrationtoken
	from users
	where registrationtime>'2007-08-16' and not confirmed and registrationtime<'2007-08-29 16:00'");
foreach (@$alerts) {
	my $id = $_->[0];
	my $email = $_->[1];
	my $name = $_->[2];
	my $token = $_->[3];
	my $url = "http://theyworkforyou.com/user/confirm/?t=$id"."::$token";
	my $out = "Hi $name,

In the last few weeks, you tried to join TheyWorkForYou. We've just
found and fixed a bug that meant confirmation emails weren't being sent
out, which means you could never confirm your registration. We're very
sorry about that, and are resending your confirmation link so you can
confirm, if you still wish to do so (if you don't, just ignore this
email).

To confirm your registration with TheyWorkForYou, please click this link:
    $url

Again, apologies for the confusion. If you have any questions,
please just reply to this email.

Yours,
Matthew, TheyWorkForYou
";
	# Send email
	my $message = mySociety::Email::construct_email({
		_body_ => $out,
		From => ['beta@theyworkforyou.com', 'TheyWorkForYou.com'],
	Subject => 'Your recent TheyWorkForYou joining',
		To => [[$email, $name]]
	});
	my $result = mySociety::EmailUtil::send_email($message, 'beta@theyworkforyou.com', $email);
	if ($result != mySociety::EmailUtil::EMAIL_SUCCESS) {
		print "Failed to send email to $email\n";
	}
}
