#!/usr/bin/perl

# parser which takes edmi.parliament.uk Early Day Motions and
# converts them to an XML file.
#
use warnings;
use strict;


my $base_url= 'http://edmi.parliament.uk/EDMi/';
my $index_url= $base_url . 'EDMList.aspx';

my $mplist_url= $base_url . 'MemberList.aspx?__EVENTTARGET=_alpha1:_';
my $Parl_Session= '875'; # hardcode to 05-06 for now
my $Parl_Session_readable= '05-06'; # hardcode to 05-06 for now
use XML::Simple;
use LWP;
my $browser = LWP::UserAgent->new;
my $dir=shift || die "usage: $0 <output dir>\n";
my %MPmap;
my %MPdate; # cache of looked up ids.
my %EDM;


{ 
	&setup_browser();
	&mplist_fetch($mplist_url);
	&indexes_fetch($index_url);

	open (OUT, ">$dir/$Parl_Session_readable.xml") || die "can't open $dir/$Parl_Session_readable.xml:$!";
	my $edm;
	$edm->{"edm"}->{"session"}->{"edm_session_name"}="$Parl_Session_readable";
	$edm->{"edm"}->{"session"}->{"edm_session_id"}="$Parl_Session";
	$edm->{"edm"}->{"session"}->{"motion"}=\%EDM;
	print OUT XMLout ($edm, KeepRoot=>1 , NoAttr=>1, AttrIndent=> 1);
	close (OUT);
}

sub setup_browser {
    $browser->agent("www.TheyWorkForYou.com EDM fetcher - run by theyworkforyou\@msmith.net");
    $browser->cookie_jar({});
}


sub indexes_fetch {
	my $url= shift;
	my $args= {};
	$args->{'_MenuCtrl:ddlSession'} = $Parl_Session;
	$args->{'ddlSortedBy'} = 1;
	$args->{'ddlStatus'} = 0;
	if (defined $ENV{DEBUG}) {print STDERR "Fetching $url\n";}
	my $response = $browser->post($url, $args); # args that don't change each time
	if($response->code == 200) {
		my $content= $response->{_content};
		#if (defined $ENV{DEBUG}) {print STDERR "$content\n";}
		&index_parse($content);
	} else {
		die "Hmm, couldn't access it: ", $response->status_line, "\n";
	}
	#foreach my $k (sort keys %args){ print "\n\n\n$k - $args{$k}\n"; }
}




# fetch each index page of MPs and parse them all
sub mplist_fetch {
	my $url= shift;
	my $args= shift;
	$args->{'_MenuCtrl:ddlSession'} = $Parl_Session;
	$args->{'ddlSortedBy'} = 1;
	$args->{'ddlStatus'} = 0;
	if (defined $ENV{DEBUG}) {print STDERR "Fetching $url\n";}
	my $return='';
	my $response = $browser->post($url, $args); # args that don't change each time
	if($response->code == 200) {
		my $content= $response->{_content};
		#if (defined $ENV{DEBUG}) {print STDERR "$content\n";}
		foreach my $letter ('a' .. 'z') {
			$return.= &mp_list_parse($letter);
		}
	} else {
		die "Hmm, couldn't access it: ", $response->status_line, "\n";
	}
}

sub index_parse {
	my $html= shift;
	$html=~ s#
?\n\s*##g;
	#print $html;
	my ($number, $parl_edmid, $edm_title, $edm_by, $signatures);
	my @matches = $html=~ m#<td class="edm-number">\s*<span id="[^"]+">\s*([\dA]+)\s*</span>\s*</td>\s*<td><a href='EDMDetails\.aspx\?EDMID=(\d+)\D.*?'>(.*?)</a></td>\s*<td><a href='EDMByMember\.aspx\?MID=\d+.*?'>([^<]+)</a>\s*</td>\s*<td class="signature-number">(\d+)</td>#micg ;
	if (defined $ENV{DEBUG}) {print STDERR "$#matches matches\n";}
	while (($number, $parl_edmid, $edm_title, $edm_by, $signatures, @matches)= @matches){
	if (defined $ENV{DEBUG}){	print  STDERR "$number $edm_title\n";}

		if (defined $EDM{$number} and defined $EDM{$number}{'title'}) {
			# you can have amendments on a page before the actual EDM being amended
			print STDERR "index is looping; bailing out\n";
			return;
		}
		$EDM{$number}{'edm_in_session'}= $number;
		$EDM{$number}{'title'}= $edm_title;
		$EDM{$number}{'primary_sponsor'}= $edm_by;
		$EDM{$number}{'signatures'}= $signatures;
		$EDM{$number}{'parliament_edmid'}= $parl_edmid;
		$EDM{$number}{'has_amendments'}||= 0;
		$EDM{$number}{'is_amendment'}= 0;

		if ($number =~ /^(\d+)A/) {
			$EDM{$1}{'has_amendments'}++;
			$EDM{$number}{'is_amendment'}=1;
		}
		&parse_motion($EDM{$number});
	}

	if ($html=~ m#<input type="submit" name="(_Pagination1:btnNextPage)"(.*?)>#) {
		my $next_page= $1;
		if ($2 !~ /disabled/) {
			if ($html=~ m#name="(__VIEWSTATE)" value="([^"]+)"#i) {
				#			&indexes_fetch($index_url, {"_MenuCtrl:hdSessionID"=>'', $next_page=> "", "$1" => "$2", "__EVENTTARGET"=> '', "__EVENTARGUMENT" => ''}); # args that could change each time
			}
		}
	}
}



sub parse_motion {
	my $info_ref= shift;
	my $response= $browser->get($base_url . 'EDMDetails.aspx?EDMID='.$info_ref->{parliament_edmid});
 	my $content= $response->{_content};
	$content=~ s#
?\n\s*##g;
	$content=~ m#<div class="DateDetail">([\d\.\s]+)</div>#;
	$info_ref->{date}=$1;
	$content=~ m#<!--\s*Motion Text Display -->\s*<p><span class=".*?">(.*?)</span>#mcgi;
	$info_ref->{motion}=$1;

	my ($memberid, $order, $name, @matches);
	my $pw_id;
	(@matches)= $content=~ m#<a\s*href='EDMByMember\.aspx\?MID=(\d+)'>\s*<span id="Sigs__ctl(\d+)_lblMember"><i>(.*?)</i></span>#mcig;
	while (($memberid, $order, $name, @matches)= @matches) {
		$info_ref->{sponsored_by}->{$order}->{name}= $name;
		#$info_ref->{sponsored_by}->{$order}->{position}= $order;
		$info_ref->{sponsored_by}->{$order}->{edm_memberid}= $memberid;
		$info_ref->{sponsored_by}->{$order}->{pw_memberid}= $MPmap{$memberid}->{"signed_$info_ref->{parliament_edmid}"};
        $info_ref->{sponsored_by}->{$order}->{date}= $MPmap{$memberid}->{"date_signed_$info_ref->{parliament_edmid}"};
	}
	(@matches)= $content=~ m#<a\s*href='EDMByMember\.aspx\?MID=(\d+)'>\s*<span id="Sigs__ctl(\d+)_lblMember">(.*?)</span>#mcig;
	while (($memberid, $order, $name, @matches)= @matches) {
		next if $name =~ /<[bi]>/;
		$info_ref->{supported_by}->{$order}->{name}= $name;
		#$info_ref->{supported_by}->{$order}->{position}= $name;
		$info_ref->{supported_by}->{$order}->{edm_memberid}= $memberid;
		#$info_ref->{supported}->[$order -1]="$name";
		$info_ref->{supported_by}->{$order}->{pw_memberid}= $MPmap{$memberid}->{"signed_$info_ref->{parliament_edmid}"};
        $info_ref->{supported_by}->{$order}->{date}= $MPmap{$memberid}->{"date_signed_$info_ref->{parliament_edmid}"};
    } 
        #print "$info_ref->{motion}\n";

}


sub mp_list_parse {
	my $letter= shift;	
	my $page= $browser->get($mplist_url . $letter);
    if (defined $ENV{DEBUG}) {print STDERR "\tfetching $mplist_url$letter\n";}
    my $lines='';
	#print $page->content;
	my (@parts, $mpid, $name, $constituency);
	@parts = $page->content =~ m#<td><a href='EDMByMember\.aspx\?MID=(\d+).*?>([^>]+)</a></td>\s*<td>([^<]+)</td>#scg;
    my $args;

	while (($mpid,$name,$constituency, @parts)= @parts) {
        my $pwid;
        &get_mp_page($mpid);
        $args->{constituency}= $constituency;
        $MPmap{$mpid}->{'constituency'}=$constituency;
        $MPmap{$mpid}->{'name'}=$name;
    }
}




sub get_mp_page {
    my $mpid = shift; 
    if (defined $ENV{DEBUG}) {print STDERR "getting mp page for $mpid\n";}
	my $page = $browser->get('http://edmi.parliament.uk/EDMi/EDMByMember.aspx?MID='. $mpid);
    my ($edmid, $edmname, $date);
    my ($name)= $page->content=~ m#Member:</i> <span class="MTitle">\s*([^>]+?)\s*</span>#mi;
    my ($constituency)= $page->content=~ m#Constituency:</i>&nbsp;<span>([^>]+?)</span>#mi;

    if (defined $ENV{DEBUG}) {print STDERR "$name - $constituency\n";}

    my (@matches) = $page->content =~ m#<tr.*?EDMDetails\.aspx\?EDMID=(\d+).*?td align="left"><a[^>]+>(.*?)</a></td>.*?(\d{2}\.\d{2}\.\d{4})#msigc;
    if (defined $ENV{DEBUG}) {print STDERR "    matches count: $#matches\n";}

    if ($#matches > 0) { #MPs who haven't taken the oath or who are ministers don't sign EDMs
        &cache_dates($name, $constituency, $mpid, 0, $matches[2], $matches[-1]);
    }
    while (($edmid, $edmname, $date, @matches)= @matches) {
        if (defined $ENV{DEBUG}) {print STDERR "    testing date $date for $mpid - edm ($edmid) $edmname\n";}


        my $pwid= &get_pwid_on_date($name, $constituency, $date,$mpid,$edmid);
        $MPmap{$mpid}->{"signed_$edmid"}=$pwid;
        $MPmap{$mpid}->{"date_signed_$edmid"}=$date;

        if (defined $ENV{DEBUG}) {print STDERR "\t$name - $constituency - $date - $pwid\n";}
    }
}




sub get_pwid_on_date {
        my ($name, $constituency, $date, $mpid, $edmid)=@_;
        my $args;
        my $pwid=0;
        my $date_send=$date;

        $name=~ s#\(.*?\)##;
        if ($name =~ m/^(.*),(.*)$/) {
            $args->{name}="$2 $1";
        } else {
            $args->{name}=$name;
        } if ($date =~ m#(\d{2})\.(\d{2})\.(\d{4})#) {
            $date_send="$3-$2-$1";
        }
        if (defined $MPdate{$mpid}->{"all"}){
            return $MPdate{$mpid}->{"all"};
        }
    
        if (defined $MPdate{$mpid}->{$date}) {
            return $MPdate{$mpid}->{$date};
        }


        $args->{constituency}= $constituency;
        $args->{command}='mp-full-cons-match';
        $args->{date}=$date_send;
        if (defined $ENV{DEBUG}) {print STDERR "\tquerying $name for $date_send\n";}
        my $response= $browser->post('http://ukparse.kforge.net/parlparse/rest.cgi', $args);

		my @lines= split /\n/, $response->{_content};
		#print $response->{_content};
        if ($lines[1] eq 'OK') {
            $lines[2]=~ m#member/(\d+)$#;
            $pwid=$1; 
        } else {
            warn "Name match failed for  $args->{name} $constituency for $date";
        } 
        $MPmap{$mpid}->{"signed_$edmid"}=$pwid;
        $MPdate{$mpid}->{$date}= $pwid;
        return ($pwid);
}

sub cache_dates {
    my ($name, $constituency, $mpid,$edmid, $first_date, $second_date)=@_;
        if (defined $ENV{DEBUG}) {print STDERR "\tcaching $name - $constituency - $mpid from $first_date to $second_date\n";}

    if (&get_pwid_on_date($name, $constituency, $first_date, $mpid,$edmid) == 
        &get_pwid_on_date($name, $constituency, $second_date, $mpid,$edmid)){
            $MPdate{$mpid}->{"all"}=$MPdate{$mpid}->{$second_date};
    }
    
}
