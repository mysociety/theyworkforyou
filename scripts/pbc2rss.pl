#!/usr/bin/perl

use warnings;
use strict;
use XML::RSS;
use config;
use DBI;
use URI::Escape;
my $dbh = DBI->connect($config::dsn, $config::user, $config::pass );

my $query = $dbh->selectall_arrayref('select gid, minor, hdate from hansard
	where htype=10 and major=6
	order by hdate desc limit 20');
my $rss = new XML::RSS (version => '1.0');
$rss->channel(
	title => "Public Bill Committee debates",
	link => "http://www.theyworkforyou.com/pbc/",
	description => "Public Bill Committee debates via TheyWorkForYou.com- http://www.theyworkforyou.com/ .",
	dc => {
		subject => '',
		creator => 'TheyWorkForYou.com',
		publisher => 'TheyWorkForYou.com',
		rights => 'Parliamentary Copyright',
		language => 'en-gb',
		ttl => 600
	},
	syn => {
		updatePeriod => 'daily',
		updateFrequency => '1',
		updateBase => '1901-01-01T00:00+00:00',
	},
);
foreach (@$query) {
	my ($gid, $minor, $hdate) = @$_;
	my ($title, $session) = $dbh->selectrow_array('select title, session from bills where id=?', {}, $minor);
	$gid =~ /standing\d\d\d\d-\d\d-\d\d_.*?_(\d\d)-\d_\d\d\d\d-\d\d-\d\d/;
	my $sitting = ordinal($1+0);
	my $u_title = uri_escape($title);
	$u_title =~ s/%20/+/g;
	$rss->add_item(
		title => "$title, $sitting sitting",
		link => "http://www.theyworkforyou.com/pbc/$session/$u_title",
		#description => $result->{body
	);
}
print $rss->as_string;

sub ordinal {
	return $_[0] . ordsuf($_[0]);
}
sub ordsuf {
	my $n = shift;
	$n %= 100;
	return 'th' if $n >= 11 && $n <= 13;
	$n %= 10;
	return 'st' if $n == 1;
	return 'nd' if $n == 2;
	return 'rd' if $n == 3;
	return 'th';
}
