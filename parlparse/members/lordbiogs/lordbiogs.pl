#! /usr/bin/env perl

# lordsbiogs.pl
# katherine.lawler@cantab.net

# Searches www.pm.gov.uk press archive for likely Lords appointment notices.
# Stores the pages it finds.
# Extracts short biographies from the notices.
#
# Output is comma-separated.
# To match to personIDs and output XML, use lordinfo2xml.pl.
#
# Notices tend to contain either lists of peers (assume title contains 'peer' and 'list') 
# or individual announcements (assume title contains 'peerage').
# Any other notices will be missed.

use warnings;
use strict;

use LWP::UserAgent;
use WWW::Mechanize;
use HTML::TreeBuilder;
use Data::Dumper;
use XML::SAX;
use XML::Simple;
use XML::Parser;

# main
{

	# For writing an intermediate CSV file

	our $STOREPAGES = 0; # value other than 1 doesn't store the fetched pages
	our @columns = ('surname','firstnames','letters','description','scraped_from','parser');
	our $cachedir = "lordbiogs_rawdata";

	my $col;
	my $print_string = '';
	for $col (@columns){
		$print_string .= $col . ',';
	}
	print "$print_string\n";

	mkdir $cachedir if ($STOREPAGES==1);

	my $m = new WWW::Mechanize;	

	# Get the first page of (10) hits.
    	$m->get('http://search.number-10.gov.uk/kbroker/number10/number10/search.lsim?qt=The+Queen+has+been+graciously+pleased&go=Go&sr=0&nh=10&cs=ISO-8859-1&sb=0&hs=0&sc=number10&oq=The+Queen+has+been+graciously+pleased&sf=&ha=368&mt=2');

    	my $url = $m->uri();
    	print STDERR "Looking at first page of search hits:\n $url \n\n"; # A search request with sr=0.

    	follow_notice_links($m, $cachedir); 

	# Follow "Next" link to next page of search hits.
	while ( $m->follow_link( text_regex => qr/Next/ ) ) {
		$url = $m->uri();				# A search request with sr=10, 20, 30, ...
		if ( $url =~ /search\.number-10/ ) { # check this really is a search hits page
			print STDERR "\nNext search page:\n\n $url \n\n";	
        		follow_notice_links($m);
		}	
    	}

    	print STDERR "No more pages of search hits. Finished finding notices.\n";

}


sub print_out {
	my $results = shift;

	my $person;
	my $col;
	#print Dumper($results); 	# <- Use for debugging: much easier to see by eye

	my $print_string = '';
	for $person (@$results){
		# Manual fixes
		if ($person->{surname} eq 'BONHAM' && $person->{letters} eq ' CARTER') {
			$person->{surname} = 'BONHAM-CARTER';
			$person->{letters} = '';
		}

		$print_string = '';
		for $col (@::columns) { 	# fill in undef values with empty string
			unless (defined ($person->{$col})){ $person->{$col} = ''; }
		}

		for $col (@::columns) { # print out results
			$print_string .= '"'.$person->{$col}.'",';
		}
		print "$print_string\n";
	}
}


sub dump_page {
    my $filename = shift;
    my $contents = shift;
    print STDERR "Trying to print to file: $filename\n";
    my $fh = new IO::File ">" . $filename;
    $fh->print($contents);
    $fh->close;
    undef $fh;
}



sub follow_notice_links {
	my $m = shift;

	my $cachedir_list = "$::cachedir/lists";
	my $cachedir_individuals = "$::cachedir/individuals";
	my $page;
	my $filename;
	my $t;
	my $n;
	my @spans;
	my $results;

	mkdir $cachedir_list if ($::STOREPAGES==1);
	mkdir $cachedir_individuals if ($::STOREPAGES==1);

    	# Follow links which could be lists of new peerages.
	$n = 1;
	while ( $m->follow_link( n => $n, text_regex => qr/peer.*list/i ) ) {

        	$page = $m->content();
		$filename = (split(/\//,$m->uri))[-1]; # take the page location as filename, plus a timestamp.
		if ($::STOREPAGES==1) {dump_page($cachedir_list.'/'.$filename, $page) }; 
		
		my $content = $m->content();
		my $len1 = length($content);
		$content =~ s{<br /><br />}{</p><p>}g;
		$t = HTML::TreeBuilder->new_from_content( $content );

		# The better fomatted notices contain <span dir="ltr"> tags.

        	if ( @spans = $t->look_down( '_tag', 'span', 'dir', 'ltr' ) ) {
			$results = parse_list($m, $t);
			print_out($results);
		}
		else {
			$results = parse_bad_list($m, $t);
			print_out($results);
		}

		$m->back();
		++$n;
	}

	# Follow links which could be individual peerage announcements.
	$n = 1;
	while ( $m->follow_link( n => $n, text_regex => qr/peerage/i ) ) {

        	$page = $m->content();
		$filename = (split(/\//,$m->uri))[-1]; # take the page location as filename, plus a timestamp.
		if ($::STOREPAGES==1) {dump_page($cachedir_individuals.'/'.$filename, $page)}; 
		
		$t = HTML::TreeBuilder->new_from_content( $m->content() );

		# Again, the better formatted notices contain <span dir="ltr"> tags.
		# Don't attempt to parse any other format of individual notices.
        	if ( @spans = $t->look_down( '_tag', 'span', 'dir', 'ltr' ) ) {
			$results = parse_indiv($m, $t);
			print_out($results);
		}

		$m->back();
		++$n;
	}

}




sub parse_list {

            # A PARSER FOR WELL-FORMATTED LISTS containing <span dir="ltr">.

	    my $m = shift;
	    my $t = shift;

	    my $current_url = $m->uri;

            my @entries = $t->look_down( '_tag', 'p' );
            my $el_entry;
	    my @spans;
	    my @all_parsed_entries = ();
	    my $parsed_entry = {};
    	    my $entry;

            for $el_entry (@entries) {
                $entry = $el_entry->as_text;
		next if ( $entry =~ /to be baron/i );
                $entry =~ s/^\s*//;	# Remove whitespace on the front.

                # If this entry looks like a person, parse it and store it.
                if ( 
                    (	 # Name and description in same tag (eg. http://www.pm.gov.uk/output/Page5729.asp)
                         $entry =~ /^([^,]*(, [A-Z][a-z]+\.?)?) ((Mc)?[A-Z-]+( OF )?[A-Z-]+)[^,]*(, ([A-Z.]+))?, (.*)$/
                    )
                    or ( # Or allow name to be in the previous tag (eg. http://www.pm.gov.uk/output/Page7482.asp)
                        ($el_entry->left()) and ( ($entry = $el_entry->left()->as_text . ', ' . $entry) =~ /^([^,]*(, [A-Z][a-z]+\.?)?) ((Mc)?[A-Z-]+( OF )?[A-Z-]+)[^,]*(, ([A-Z.]+))?, (.*)$/ )
                    )
                  ) {

                    my ( $firstnames, $title, $surname, $letters, $description ) = ( $1, $2, $3, $7, $8 );

		    # If firstnames ends with an uppercase string, move this to surname and the surname to letters (repeatedly until only one upper case string left in firstnames)
                    while ( ($firstnames =~ /^(.*) ([A-Z][A-Z']+)\s*$/) ) {
                            $letters .= ' ' . $surname;
                            $surname    = $2;
                            $firstnames = $1;
                    }

		    # If description seems to contain a whole other person, flag it as bad and don't store it.
		    # - spots a second CAPITALIZED string in a context that looks like a person's name.
                    if ( ( $description =~ /([^,]*(, [A-Z][a-z]+)?) ([A-Z]+( OF )?[A-Z]+)[^,]*(,? ([A-Z]+))?, (.*)/ ) 
		    	and # but allow common CAPITALIZED strings: HM, MAFF, PPS, FCO, SDP
			 not ( ($3 eq 'HM') or ($3 eq 'MAFF') or ($3 eq 'PPS') or ($3 eq 'FCO') or ($3 eq 'SDP') )
		       ) {
                        print STDERR " -- parser1: unreliable line parse, not stored.\n" 
					. " description possibly contains another person:\n"
					. $entry . "\n\n";
		    }
		    else {

                    	$parsed_entry = {
                        	surname      => $surname,
                        	firstnames   => $firstnames,
                        	letters      => $letters,
                        	description  => $description,
				scraped_from => $current_url,
				parser	     => 'parser1'
                    	};
                    	push @all_parsed_entries, $parsed_entry;
	    	    }
                }
		#else {
		#    print STDERR "$current_url: Ignoring the following string (no match): $entry\n\n";
		#}
            }
	    return \@all_parsed_entries;
}


sub parse_bad_list {

	    # A PARSER FOR OTHER LISTS based on splitting entire page content by punctuation(!).
	    # eg. http://www.pm.gov.uk/output/Page2796.asp

	    my $m = shift;
	    my $t = shift;

	    my @all_parsed_entries = ();
	    my $parsed_entry = {};
	    my @entries;
    	    my $entry;

	    my $current_url = $m->uri();

            # Retrieve all text content and split by fullstops/colons.
	    $t = $t->as_text;
	    $t =~ s{(Lopex plc|Pendle Borough Council)}{$1.}gm; # manual
            @entries = split( /[\.:]/, $t );

            # Skip down to 'to be baronesses'.
            while ( not( $entries[0] =~ /to be baron/i ) ) { shift @entries; }
            for $entry (@entries) {
                next if ( $entry =~ /to be baron/i );
                $entry =~ s/^\s*//;

                #print $entry . "\n";
                # If entry looks like a person, parse it.
                if ( $entry =~ /^([^,]*(, [A-Z][a-z]+\.?)?) ((Mc)?[A-Z-]+( OF )?[A-Z-]+)[^,]*(, ([A-Z.]+))?, (.*)$/ ) {
                    #print $entry. "n";
                    my ( $firstnames, $title, $surname, $letters, $description ) = ( $1, $2, $3, $7, $8 );

		    # If firstnames ends with an uppercase string, move this to surname and the surname to letters.
                    while ( $firstnames =~ /^(.*) ([A-Z][A-Z']+)\s*$/ ) {
                            $letters .= ' ' . $surname;
                            $surname    = $2;
                            $firstnames = $1;
                    }

		    # If description seems to contain a whole other person, flag it as bad and don't store it.
		    # - spots a second CAPITALIZED string in a context that could be another person's name.
                    if ( ( $description =~ /([^,]*(, [A-Z][a-z]+)?) ([A-Z]+( OF )?[A-Z]+)[^,]*(,? ([A-Z]+))?, (.*)/ ) 
		    	and # but allow common CAPITALIZED strings: HM, MAFF, PPS, FCO, SDP
			 not ( ($3 eq 'HM') or ($3 eq 'MAFF') or ($3 eq 'PPS') or ($3 eq 'FCO') or ($3 eq 'SDP') )
		       ) {
                        print STDERR " -- parser2: unreliable line parse, not stored.\n"
				 	. " description possibly contains another person\n"
					. " (missing fullstop at end of previous line?):\n"
					. $entry . "\n\n";
                    }
                    else {
                        $parsed_entry = {
                            surname      => $surname,
                            firstnames   => $firstnames,
                            letters      => $letters,
                            description  => $description,
                            scraped_from => $current_url,
			    parser	 => 'parser2'
                        };
                        push @all_parsed_entries, $parsed_entry;
                    }
                }
		#else {
		#    print STDERR "$current_url: Ignoring the following (no match): $entry\n\n";
		#}

            }
        return \@all_parsed_entries;
}

sub parse_indiv {
	       	# A PARSER FOR INDIVIDUAL NOTICES containing <span dir="ltr">

		my $m = shift;
		my $t = shift;

		
	        my @all_parsed_entries = ();
	        my $parsed_entry = {};
    	        my $entry;

		my $current_url = $m->uri();


        	my @entries = $t->look_down( '_tag', 'p' );
        	my $el_entry;

        	for $el_entry (@entries) {
            		$entry = $el_entry->as_text;
            		$entry =~ s/^\s*//;	# Remove whitespace on the front.
			#print $entry . "\n";

            		# If this entry looks like a retirement appointment, parse it and store it.
            		if (  # Retirement (eg. http://www.pm.gov.uk/output/Page3628.asp)
                     		$entry =~ / upon (.*) ((on|following) (his|her) retirement (.*))$/
               		   ) {

				#print $entry. "\n";
                		my ( $name, $description ) = ( $1, $2 );
				my $firstnames = $name;
				my $surname = '';
				my $letters = '';

				# If surname ends with uppercase strings, move these to letters 
                    		while ( ($firstnames =~ /^(.*) ([A-Z][A-Z]+)$/) ) {
                            		$letters .= ' ' . $2;
                            		$firstnames = $1;
                    		}

				# Move last Capitalized Word to surname
				if ( $firstnames =~ /^(.*) ([A-Z]\w+)$/ ) {
					$surname = $2;
					$firstnames = $1;
				}

                		$parsed_entry = {
					firstnames   => $firstnames,
                    			surname      => $surname,
					letters      => $letters,
                    			description  => $description,
                    			scraped_from => $current_url,
					parser       => 'parser3'
                			};
                		push @all_parsed_entries, $parsed_entry;
            		}
			elsif ($entry =~ /The Queen (is|has been) graciously pleased/){ # Follow up by hand?
				print STDERR "\n -- $current_url is probably a skipped peerage announcement.\n\n";
			}
			#else {
			#	print STDERR "$current_url: Ignoring the following string (no match):". substr($entry,0,50)."\n\n";
			#}
        	}
	return \@all_parsed_entries;
}


sub read_people {
	# Read people.xml file to get all IDs
	print STDERR "Getting people.xml\n";
	my $ref = new WWW::Mechanize;
	$ref->get('http://ukparse.kforge.net/svn/parlparse/members/people.xml')
		or die $!;

	print STDERR "Parsing people xml\n"; 
	my $xmlparser = new XML::Parser(Style=>'Objects');
	my $people = $xmlparser->parse($ref->content())->[1];

	# Surname is usually the only link between pre- and post- peerage name.
	# (At least, that's true if just using the people.xml reference data.)
	
	# Make list of [latestname, personID]
	# Only include people with <office id="uk.org.publicwhip/lord/*" current="yes"/>.
	
	print Dumper($people->[2]->[0]->{id});

	my $person;
	my %lords_fullname;
	my $personIDs = {};
	my $line_lordList;
	my ($lordname, $surname, $this_personID, @names, $title);
	my $was_lord = undef;
	my $office;

	print Dumper($people->[6]);
	for $person (@$people){

		print Dumper($person);
		$lordname = $person->[0]->{latestname};  # eg. Lord Anderson of Swansea
		$this_personID = (split('person/',$person->[0]->{id}))[1];

		# If person is (or has been) a lord, store them.
		for $office (@$person){
			if( $office->{id} =~ /lord/ ){$was_lord = 1;}
		}
		print Dumper($office);
		if ($was_lord){

			# Assume the second word is the surname.
			# Ignore anyone starting with 'The'
			#    (eg. The Earl of Caithness, The Bishop of Chelmsford)
			#    These are unlikely to appear in the parsed notices file anyway.
	
			@names = split(/ /, $lordname);
			$title = $names[0];
			$surname = uc($names[1]);
			unless ($title eq 'The'){
				$this_personID = $line_lordList->[0];
				push @{$personIDs->{$surname}}, $this_personID;
				$lords_fullname{ $this_personID } = $lordname; # incase it's useful
			}
		}
		$was_lord = undef;
	}
	return $personIDs;
}


