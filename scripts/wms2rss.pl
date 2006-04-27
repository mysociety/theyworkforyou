#!/usr/bin/perl

use warnings;
use strict;
use XML::RSS;
use config;
use DBI;
my $dbh = DBI->connect($config::dsn, $config::user, $config::pass );


my $query= $dbh->prepare("select hdate from hansard where major='4' order by hdate desc limit 1");
$query->execute();
my ($date)= $query->fetchrow_array();

$query = $dbh->prepare("
	SELECT e.body, h.hdate, h.htype, h.gid, h.subsection_id, h.section_id,
		h.epobject_id, m.house, m.title, m.first_name, m.last_name, m.constituency, m.person_id
	FROM hansard h, epobject e, member m
	WHERE h.major='4' AND htype='12' AND h.hdate='$date' AND section_id != 0 AND subsection_id != 0
	AND h.epobject_id = e.epobject_id AND h.speaker_id = m.member_id
	ORDER BY h.epobject_id desc");
$query->execute;

my $rss = new XML::RSS (version => '1.0');
$rss->channel(
	title => "Written Ministerial Statements",
	link => "http://www.theyworkforyou.com/wms/",
	description => "Written Ministerial Statements via TheyWorkForYou.com- http://www.theyworkforyou.com/ .",
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
my $local_office_query;
my ($title, $posn, $dept);
while ($result = $query->fetchrow_hashref) {
	$local_title_query = $dbh->prepare("select body from epobject where epobject_id=$result->{subsection_id}");
	$local_title_query->execute;
	($title)=$local_title_query->fetchrow_array; # title, not dept.
	$local_office_query = $dbh->prepare('SELECT position,dept FROM moffice WHERE person=' .$result->{person_id} . ' ORDER BY from_date DESC LIMIT 1');
	$local_office_query->execute;
	($posn,$dept) = $local_office_query->fetchrow_array;
	$title .= ' (' . member_full_name($result);
	$title .= ", $posn, $dept" if ($posn && $dept);
	$title .= ')';

	($id)= $result->{gid} =~ m#\/([^/]+)$#;
	$rss->add_item(
		title=>$title,
		link=>'http://www.theyworkforyou.com/wms/?id=' . $id,
		description=>$result->{body}
	);
}
print $rss->as_string;

sub member_full_name {
	my $result = shift;
	my $house = $result->{house};
	my $title = $result->{title};
	my $first_name = $result->{first_name};
	my $last_name = $result->{last_name};
	my $con = $result->{constituency};
	my $s = 'ERROR';
	if ($house == 1) {
		$s = $first_name . ' ' . $last_name;
		if ($title) {
			$s = $title . ' ' . $s;
		}
	} elsif ($house == 2) {
		$s = '';
		$s = 'The ' if (!$last_name);
		$s .= $title;
		$s .= ' ' . $last_name if $last_name;
		$s .= ' of ' . $con if $con;
	}
	return $s;
}
