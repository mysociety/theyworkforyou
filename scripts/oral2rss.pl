#!/usr/bin/perl

use warnings;
use strict;
use XML::RSS;
use config;
use DBI;
my $dbh = DBI->connect($config::dsn, $config::user, $config::pass );
my $Output_Dir= shift || die "usage: $0 output_dir/\n";

my $query= $dbh->prepare("
	select hansard.*, epobject.body from hansard, epobject where hdate >= date_sub(now(), interval 30 day) and major=1 and htype= 10 and hansard.epobject_id = epobject.epobject_id and epobject.body like 'Oral Answers%'
	");
$query->execute();



my %oral;
while (my $result = $query->fetchrow_hashref) {

	my $subquery= $dbh->prepare(" select epobject.epobject_id,
				      epobject.body,
				      hansard.gid
				 from epobject, hansard
				where hansard.section_id=?
				  and hansard.epobject_id= epobject.epobject_id
			  	  and htype=11
			  ");
	$subquery->execute($result->{epobject_id});	
	while (my $r= $subquery->fetchrow_hashref) {
    		my ($id)= $r->{gid} =~ m#\/([^/]+)$#;
    		push @{$oral{$result->{body}}}, [$result->{epobject_id}, $result->{hdate}, $id , $r->{body}] ;
	}
}


foreach my $area (keys %oral) {
my $rss = new XML::RSS (version => '1.0');
$rss->channel(
	title => "$area",
	link => "http://www.theyworkforyou.com/debates/",
	description => "$area via TheyWorkForYou.com - http://www.theyworkforyou.com/ .",
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

	my ($dept_name)= $area =~ m#; (.*)#;

	foreach my $topic (@{$oral{$area}}) {

		$rss->add_item(
			title=>"$dept_name &mdash; $topic->[3]",
			link=>'http://www.theyworkforyou.com/debates/?id=' . $topic->[2],
 			description=>"$area - $topic->[3]"
 		);
	}
	my $filename= $dept_name;
	$filename =~ s/[^a-z0-9]//gi;

	open (OUT, ">$Output_Dir/$filename.rss") || die "can't open $Output_Dir/$filename.rss:$!";
	print OUT $rss->as_string;
	close (OUT);
}

