#! /usr/bin/perl -w
#
# One off because we didn't send any email for 2-3 weeks.
# 
# $Id: one-off-email-probs-alerts.pl,v 1.1 2008-01-25 19:57:31 twfy-live Exp $

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
	select alert_id, email, registrationtoken, criteria
	from alerts
	where created>'2007-08-16' and not confirmed and created<'2007-08-29 16:00'");
my %out;
foreach (@$alerts) {
	my $id = $_->[0];
	my $email = $_->[1];
	my $token = $_->[2];
	my $criteria = $_->[3];

	if ($criteria =~ /speaker:(\d+)/) {
		my $name = $dbh->selectrow_array("select concat(first_name,' ',last_name) from member where person_id=? limit 1", {}, $1);
		$criteria =~ s/speaker:\d+/spoken by $name/;
	}
	my $url = "http://theyworkforyou.com/alert/confirm/?t=$id"."::$token";
	$out{$email}{$criteria} = $url;
}

foreach my $email (sort keys %out) {
	my $out = '';
	foreach my $criteria (keys %{$out{$email}}) {
		my $url = $out{$email}{$criteria};
		$out .= "To confirm your alert for: '$criteria', please click this link:
    $url

";
	}
	$out = "Hi,

In the last few weeks, you tried to create an alert on TheyWorkForYou.
We've just found and fixed a bug that meant confirmation emails weren't
being sent out, which means you could never confirm your alert. We're
very sorry about that, and so are resending your confirmation link so
you can confirm your alert (obviously, if you've changed your mind and
don't want an alert any more, just ignore this email).

${out}Again, apologies for the confusion. If you have any questions,
please just reply to this email.

Yours,
Matthew, TheyWorkForYou
";
	# Send email
	my $message = mySociety::Email::construct_email({
		_body_ => $out,
		From => ['beta@theyworkforyou.com', 'TheyWorkForYou.com'],
	Subject => 'Your recent TheyWorkForYou email alert subscription',
		To => $email
	});
	my $result = mySociety::EmailUtil::send_email($message, 'beta@theyworkforyou.com', $email);
	if ($result != mySociety::EmailUtil::EMAIL_SUCCESS) {
		print "Failed to send email to $email\n";
	}
}
