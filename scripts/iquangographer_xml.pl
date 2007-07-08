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
my %variables_max;
my %skip_these;
my $sequences;


{
	&setup;

	&get_variables();
	&make_structure_and_output();

}

sub get_variables {	
	my $member_query= $dbh->prepare("select * from member where left_reason='still_in_office' and house = 1 ");
	$member_query->execute();
	my $member_info_query= $dbh->prepare("select * from memberinfo where member_id= ? ");
	my $person_info_query= $dbh->prepare("select * from personinfo where person_id = ? ");

	while (my $result= $member_query->fetchrow_hashref) {
		$member->{$result->{'member_id'}}->{'memberdata'}=$result;
		$member_info_query->execute($result->{'member_id'});
		while (my $m_r= $member_info_query->fetchrow_hashref) {
			$member->{$result->{'member_id'}}->{'values'}->{$m_r->{'data_key'}}=$m_r->{'data_value'};
			$memberinfo_keys{$m_r->{'data_key'}}++;

		}


		$person_info_query->execute($result->{'person_id'});
		while (my $m_r= $person_info_query->fetchrow_hashref) {
		
			if ($m_r->{'data_key'} =~ m#expenses#i) {
                        	my ($year, $name);
                        	next if $m_r->{'data_key'} =~ m#outof#i;
                        	next if $m_r->{'data_key'} =~ m#quintile#i;
	
                        	if (($year, $name)= $m_r->{'data_key'}=~ m#expenses(\d+)_(.*)$#) {
                                	$member->{$result->{'member_id'}}->{'expenses'}->{$name}->{$year}=$m_r->{'data_value'};
                                	$memberinfo_keys{$name}++;
                        	}
			} else {
				$member->{$result->{'member_id'}}->{'values'}->{$m_r->{'data_key'}}=$m_r->{'data_value'};
				$memberinfo_keys{$m_r->{'data_key'}}++;
			}	

		}

	}

}




sub make_structure_and_output{
	my $index=0;
	my $output;	
	foreach my $mp (sort {
				(lc($member->{$a}->{'memberdata'}->{'last_name'}) cmp lc($member->{$b}->{'memberdata'}->{'last_name'})) or
				(lc($member->{$a}->{'memberdata'}->{'first_name'}) cmp lc($member->{$b}->{'memberdata'}->{'first_name'})) or
				(lc($member->{$a}->{'memberdata'}->{'constituency'}) cmp lc($member->{$b}->{'memberdata'}->{'constituency'}) )
			} keys %{$member}) {

		$output->{'points'}->{'point'}[$index]->{'name'}= "$member->{$mp}->{'memberdata'}->{'first_name'} $member->{$mp}->{'memberdata'}->{'last_name'}, $member->{$mp}->{'memberdata'}->{'constituency'}";
		$output->{'points'}->{'point'}[$index]->{'for_matching'}= $member->{$mp}->{'memberdata'}->{'constituency'} || '';
		$output->{'points'}->{'point'}[$index]->{'group'}= $member->{$mp}->{'memberdata'}->{'party'};
		$output->{'points'}->{'point'}[$index]->{'pointsize'}= 3;
		$output->{'points'}->{'point'}[$index]->{'colour'}= &get_colour($member->{$mp}->{'memberdata'}->{'party'}) || '000000';
		$output->{'points'}->{'point'}[$index]->{'link'}= 'http://www.theyworkforyou.com/mp/?m=' . $mp;

		foreach my $data_key (keys %{$member->{$mp}->{'values'}}){ 
			my $skip=0;
			foreach my $k (keys %skip_these) { $skip=1 if $data_key =~ m#$k#i; }
			next if ($data_key =~ m#expenses#i and $data_key !~ m#total#i);
			next if $skip;


			if ($member->{$mp}->{'values'}->{$data_key}=~ m#%#) {
					$varinfo{$data_key}->{'unit'}='%';
					$member->{$mp}->{'values'}->{$data_key} =~ s#%##;
			} elsif ($data_key=~ m#expenses# and $data_key !~ m#rank#) {
					$varinfo{$data_key}->{'unit'}='&pound;';
			}

			$output->{'points'}->{'point'}[$index]->{'variables'}->{$data_key}= $member->{$mp}->{'values'}->{$data_key};

			if (not defined $variables_max{$data_key} ){
				if (not $member->{$mp}->{'values'}->{$data_key} =~ m#[^\.\d \-]#) {
					$variables_max{$data_key}= $member->{$mp}->{'values'}->{$data_key}
				}
			} else {
				if (not $member->{$mp}->{'values'}->{$data_key} =~ m#[^\.\d \-]#) {
					if ($variables_max{$data_key} <  $member->{$mp}->{'values'}->{$data_key} ) {
						#warn "$data_key $variables_max{$data_key} <  $member->{$mp}->{'values'}->{$data_key} "; 
						$variables_max{$data_key}= $member->{$mp}->{'values'}->{$data_key};
					}
				}
			}
		}

		my $sequenceno=0;
                foreach my $name (keys %{$member->{$mp}->{'expenses'}}){ 

                        next if $name =~ m#outof#i;
                        next if $name =~ m#quintile#i;
                        
                        if ($member->{$mp}->{'expenses'}->{$name}=~ m#%#) {
                                        $varinfo{$name}->{'unit'}='%';
                                        $member->{$mp}->{'expenses'}->{$name} =~ s#%##;
                        } elsif ($name=~ m#expenses# and $name !~ m#rank#) {
                                        $varinfo{$name}->{'unit'}='&pound;';
                        }

                        foreach my $year (keys %{$member->{$mp}->{'expenses'}->{$name}} ) {
                                my $amount= $member->{$mp}->{'expenses'}->{$name}->{$year};     

                                #$output->{'points'}->{'point'}[$index]->{'sequences'}->{'sequence'}[$sequenceno]->{$name}->{'s'.$year}=$year;
                                $output->{'points'}->{'point'}[$index]->{'sequences'}->{$name}->[0]->{'s'.$year}= $amount;

                                if (not defined $variables_max{$name} ){
                                        if (not $member->{$mp}->{'expenses'}->{$name}->{$year} =~ m#[^\.\d \-]#) {
                                                $variables_max{$name}= $member->{$mp}->{'expenses'}->{$name}->{$year};
                                        }
                                } else {
                                        if (not $member->{$mp}->{'expenses'}->{$name}->{$year} =~ m#[^\.\d \-]#) {
                                                if ($variables_max{$name} <  $member->{$mp}->{'expenses'}->{$name}->{$year} ) {
                                                        $variables_max{$name}= $member->{$mp}->{'expenses'}->{$name}->{$year};
                                                }
                                        }
                                }
                                $sequences->{$name}->{$year}=1;
                        }
                        $sequenceno++;

                }       

		$index++;
	}	


	foreach my $key (keys %memberinfo_keys) {
		if ($key =~ m#swing#i) { $varinfo{$key}->{'unit'}='%'; }	
		if ($key =~ m#expenses#i) { $varinfo{$key}->{'unit'}='&pound;'; }	
	}

	$index=0;

	# do this second so that the above can populate some of out fields
	foreach my $key (keys %memberinfo_keys) {
		my $skip=0;
		foreach my $k (keys %skip_these) { $skip=1 if $key =~ m#$k#i; }
		#next if ($key=~ m#expenses#i and $key !~ m#total#i);
		next if $skip;
		$output->{'variables'}->{'variable'}[$index]->{'name'}= $key;
		$output->{'variables'}->{'variable'}[$index]->{'title'}= $varinfo{$key}->{'name'} || &make_name($key);
		$output->{'variables'}->{'variable'}[$index]->{'unit'}= $varinfo{$key}->{'unit'} || '';	
		$output->{'variables'}->{'variable'}[$index]->{'min'}= 0;
		
		$output->{'variables'}->{'variable'}[$index]->{'max'}= $variables_max{$key};

                if (defined $sequences->{$key}) {
                        my $sequence=0;
                        next if $key =~ m#outof#i;
                        next if $key =~ m#quintile#i;
                        foreach my $v (sort keys %{$sequences->{$key}}) {
                                $output->{'variables'}->{'variable'}[$index]->{'sequences'}->[0]->{'s'.$v}= $v;
                        }
                        unless ($key =~ m#rank#) {
				$output->{'variables'}->{'variable'}[$index]->{'unit'}= '&pound;';
			}
                        $sequence++;
                }

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



sub setup {
	$skip_these{'url'}='regexp';
	$skip_these{'quintile'}='regexp';
	$skip_these{'fuzzy'}='regexp';
	$skip_these{'category'}='regexp';
	$skip_these{'date'}='regexp';
	$skip_these{'dreammp'}='regexp';
	$skip_these{'guardian_mp_summary'}='regexp';
	$skip_these{'mp_website'}='regexp';
	$skip_these{'outof'}='regexp';
	$skip_these{'html'}='regexp';
	$skip_these{'constituency'}='regexp';
	$skip_these{'description'}='regexp';
	$skip_these{'party'}='regexp';
	$skip_these{'notes'}='regexp';
	$skip_these{'name'}='regexp';
	$skip_these{'content'}='regexp';
	$skip_these{'wrans_departments'}='regexp';
	$skip_these{'maiden'}='regexp';
	$skip_these{'subjects'}='regexp';

	$varinfo{'col1'}->{'name'}='Additional Costs Allowance';
	$varinfo{'col2'}->{'name'}='London Supplement';
	$varinfo{'col3'}->{'name'}='Incidental Expenses Provision';
	$varinfo{'col4'}->{'name'}='Staffing Allowance';
	$varinfo{'col5'}->{'name'}="Members' Travel";
	$varinfo{'col6'}->{'name'}="Members' Staff Travel";
	$varinfo{'col7'}->{'name'}='Centrally Purchased Stationary';
	$varinfo{'col7a'}->{'name'}='Stationary: associated postage';
	$varinfo{'col8'}->{'name'}='Centrally Provided Computer Equipment';
	$varinfo{'col9'}->{'name'}='Other Costs';

	$varinfo{'col1_rank'}->{'name'}='Additional Costs Allowance (rank)';
	$varinfo{'col2_rank'}->{'name'}='London Supplement (rank)';
	$varinfo{'col3_rank'}->{'name'}='Incidental Expenses Provision (rank)';
	$varinfo{'col4_rank'}->{'name'}='Staffing Allowance (rank)';
	$varinfo{'col5_rank'}->{'name'}="Members' Travel (rank)";
	$varinfo{'col6_rank'}->{'name'}="Members' Staff Travel (rank)";
	$varinfo{'col7_rank'}->{'name'}='Centrally Purchased Stationary (rank)';
	$varinfo{'col7a_rank'}->{'name'}='Stationary: associated postage (rank)';
	$varinfo{'col8_rank'}->{'name'}='Centrally Provided Computer Equipment (rank)';
	$varinfo{'col9_rank'}->{'name'}='Other Costs (rank)';
}
