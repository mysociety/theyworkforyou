#!/usr/bin/perl

# this code is in the public domain.
# Does the graphing xml for the new version of mp tables.

use warnings;
use strict;
use config;

use DBI; 
use Data::Dumper;
use XML::Simple;
my $dbh = DBI->connect($config::dsn, $config::user, $config::pass );
my %memberinfo_keys;
my $member;
my $output_xml;
my %varinfo;

my %skip_these;

$skip_these{'url'}='regexp';
$skip_these{'quintile'}='regexp';
$skip_these{'outof'}='regexp';

{

	&get_variables();
	&make_structure_and_output();

}

sub get_variables {	
	my $member_query= $dbh->prepare("select * from member where left_reason='still_in_office' and house = 1 ");
	$member_query->execute();
	my $member_info_query= $dbh->prepare("select * from memberinfo where member_id= ? ");

	while (my $result= $member_query->fetchrow_hashref) {
		$member->{$result->{'member_id'}}->{'memberdata'}=$result;
		$member_info_query->execute($result->{'member_id'});
		while (my $m_r= $member_info_query->fetchrow_hashref) {
			$member->{$result->{'member_id'}}->{'values'}->{$m_r->{'data_key'}}=$m_r->{'data_value'};
			$memberinfo_keys{$m_r->{'data_key'}}++;
		}

	}

}

sub make_structure_and_output{
	my $index=0;
	my $output;	
	foreach my $mp (sort keys %{$member}) {
		$output->{'points'}->{'point'}[$index]->{'name'}= "$member->{$mp}->{'memberdata'}->{'first_name'} $member->{$mp}->{'memberdata'}->{'last_name'}, $member->{$mp}->{'memberdata'}->{'constituency'}";
		$output->{'points'}->{'point'}[$index]->{'group'}= $member->{$mp}->{'memberdata'}->{'party'};
		$output->{'points'}->{'point'}[$index]->{'colour'}= &get_colour($member->{$mp}->{'memberdata'}->{'party'});
		$output->{'points'}->{'point'}[$index]->{'link'}= 'http://www.theyworkforyou.com/mp/?m=' . $mp;

		foreach my $data_key (keys %{$member->{$mp}->{'values'}}){ 
			my $skip=0;
			foreach my $k (keys %skip_these) { $skip=1 if $data_key =~ m#$k#i; }
			next if $skip;

			$output->{'points'}->{'point'}[$index]->{'variables'}->{$data_key}= $member->{$mp}->{'values'}->{$data_key};

			if ($member->{$mp}->{'values'}->{$data_key}=~ m#\%#) {
					$varinfo{$data_key}->{'value'}='%';
			}
		}	
		$index++;
	}	

	$index=0;

	# do this second so that the above can populate some of out fields
	foreach my $key (keys %memberinfo_keys) {
		my $skip=0;
		foreach my $k (keys %skip_these) { $skip=1 if $key =~ m#$k#i; }
		next if $skip;
		$output->{'variables'}->{'variable'}[$index]->{'name'}= $key;
		$output->{'variables'}->{'variable'}[$index]->{'title'}= $varinfo{$key}->{'name'} || &make_name($key);
		$output->{'variables'}->{'variable'}[$index]->{'unit'}= $varinfo{$key}->{'unit'} || '';	
		$output->{'variables'}->{'variable'}[$index]->{'min'}= 0;
		
		my $q= $dbh->prepare("select data_value from memberinfo where data_key=? order by data_value desc limit 1");
		$q->execute($key);
		($output->{'variables'}->{'variable'}[$index]->{'max'})= $q->fetchrow_array;

		$index++;
	}



	print XMLout($output, AttrIndent =>1, NoAttr=>1, RootName=>'iquango', XMLDecl=>1);
}


sub get_colour {
	my $party= shift;

	#useful for colours http://halflife.ukrpack.net/csfiles/help/colors.shtml
	if ($party=~ m#Con#) {
		return '0000ff';
	} elsif ($party=~ m#Ind Lab#) {
		return 'aa0000';
	} elsif ($party=~ m#Lab#) {
		return 'ff0000';
	} elsif ($party=~ m#LibDem#) {
		return 'ffd700';
	} elsif ($party=~ m#Green#) {
		return '008000';
	} elsif ($party=~ m#Sinn#) {
		return '006400';
	} elsif ($party=~ m#SNP#) {
		return 'ffff00';
	} else {
		return 'D2691E'
	}
}

sub make_name {
	my $name= shift;
	$name=~ s#_# #g;

	$name=~ s#public.?whip##i;

	return $name;
}
