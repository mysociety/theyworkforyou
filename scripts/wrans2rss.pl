#!/usr/bin/perl

use warnings;
use strict;
use XML::RSS;
use config;
use DBI;
my $dbh = DBI->connect($config::dsn, $config::user, $config::pass );

my $query= $dbh->prepare("select hdate from hansard where major='3' order by hdate desc limit 1");
$query->execute();
my ($date)= $query->fetchrow_array();

$query = $dbh->prepare("SELECT e.body, h.hdate, h.htype, h.gid, h.subsection_id, h.section_id, h.epobject_id FROM hansard h, epobject e WHERE h.major='3' AND htype='12' AND hdate='$date' AND h.epobject_id = e.epobject_id order by h.epobject_id desc");
$query->execute;
my $rss = new XML::RSS (version => '1.0');
$rss->channel(
	title => "Written Answers",
	link => "http://www.theyworkforyou.com/wrans/",
	description => "Written Answers via TheyWorkForYou.com- http://www.theyworkforyou.com/ .",
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
my $result;
my $id;
my $local_title_query;
my $title;
while ($result = $query->fetchrow_hashref) {
	$local_title_query = $dbh->prepare("select body from epobject where epobject_id=$result->{subsection_id}");
	$local_title_query->execute;
	($title)=$local_title_query->fetchrow_array; # title, not dept.
	($id)= $result->{gid} =~ m#\/([^/]+)$#;

        next unless ($id =~ /q\d+$/);

	$rss->add_item(
		title=>$title,
		link=>'http://www.theyworkforyou.com/wrans/?id=' . $id,
		description=>$result->{body}
	);
}
print $rss->as_string;


