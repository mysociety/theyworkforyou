#!/usr/bin/perl

use warnings;
use strict;
use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use config;
use DBI;
use XML::RSS;

my $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });

my $query= $dbh->prepare("select hdate from hansard where major='5' order by hdate desc limit 1");
$query->execute();
my ($date)= $query->fetchrow_array();

# do we need to do something date related here?
$query = $dbh->prepare("SELECT e.body, h.hdate, h.htype, h.gid, h.subsection_id, h.section_id, h.epobject_id FROM hansard h, epobject e WHERE h.major='5' AND htype='10' AND h.hdate='$date' AND h.epobject_id = e.epobject_id order by h.epobject_id desc");
$query->execute;

my $rss = new XML::RSS (version => '1.0');
$rss->channel(
	title => "Northern Ireland Assembly Debates",
	link => "http://www.theyworkforyou.com/ni/",
	description => "Northern Ireland Assembly Debates via TheyWorkForYou.com - http://www.theyworkforyou.com/ .",
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
my $body;
my $id;
while ($result = $query->fetchrow_hashref) {
    ($id)= $result->{gid} =~ m#\/([^/]+)$#;

    $body.= "<li>\n    <a href=\"http://www.theyworkforyou.com/ni/?id=$id\">$result->{body}</a>\n</li>\n";    
}
$rss->add_item(
	title=>"Northern Ireland Assembly debates for $date",
	link=>'http://www.theyworkforyou.com/ni/?d=' . $date,
	description=>"<ul>\n\n$body\n\n</ul>\n"
	);
print $rss->as_string;

