#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;

# $Id: mpinfoin.pl,v 1.8 2006-10-18 22:38:53 twfy-live Exp $

# Reads XML files with info about MPs and constituencies into
# the memberinfo table of the fawkes DB

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use config; # see config.pm.incvs

use XML::Twig;
use DBI; 
use File::Find;
use HTML::Entities;
use Data::Dumper;
use Syllable;

# Fat old hashes intotwixt all the XML is loaded and colated before being squirted to the DB
my $memberinfohash;
my $personinfohash;
my $consinfohash;

# Find latest register of members interests file
chdir $config::pwdata;
my $regmemfile = "";
find sub { $regmemfile = $_ if /^regmem.*\.xml$/ and $_ ge $regmemfile}, 'scrapedxml/regmem/';

# Read in all the files
my $twig = XML::Twig->new(
        twig_handlers => { 
                'memberinfo' => \&loadmemberinfo,
                'personinfo' => \&loadpersoninfo,
                'consinfo' => \&loadconsinfo,
                'regmem' => \&loadregmeminfo
        }, output_filter => 'safe' );
# TODO: Parse ALL regmem in forwards chronological order, so each MP (even ones left parl) gets their most recent one
$twig->parsefile($config::pwdata . "scrapedxml/regmem/$regmemfile", ErrorContext => 2);
$twig->parsefile($config::pwmembers . "wikipedia-commons.xml", ErrorContext => 2);
$twig->parsefile($config::pwmembers . "wikipedia-lords.xml", ErrorContext => 2);
$twig->parsefile($config::pwmembers . "diocese-bishops.xml", ErrorContext => 2);
$twig->parsefile($config::pwmembers . "edm-links.xml", ErrorContext => 2);
$twig->parsefile($config::pwmembers . "bbc-links.xml", ErrorContext => 2);
# TODO: Update Guardian links
$twig->parsefile($config::pwmembers . "guardian-links.xml", ErrorContext => 2);
$twig->parseurl("http://www.writetothem.com/stats/2005/mps?xml=1");
$twig->parseurl("http://www.publicwhip.org.uk/feeds/mp-info.xml"); # i love twig
$twig->parseurl("http://www.publicwhip.org.uk/feeds/mp-info.xml?house=lords"); # i love twig
# TODO: Add smoking, parliamentary scrutiny
# Iraq war, terrorism law, Hunting, Foundation hospitals, gay vote,
# no2id, top-up fees, abolish parliament, no smoking
# TODO: Think about how these (esp no2id) might change now after election
foreach my $dreamid (219, 258, 358, 363, 826, 230, 367, 856, 811) {
        $twig->parseurl("http://www.publicwhip.org.uk/feeds/mpdream-info.xml?id=$dreamid");
}
$twig->parsefile($config::pwmembers . "expenses200405.xml", ErrorContext => 2);
$twig->parsefile($config::pwmembers . "expenses200304.xml", ErrorContext => 2);
$twig->parsefile($config::pwmembers . "expenses200203.xml", ErrorContext => 2);
$twig->parsefile($config::pwmembers . "expenses200102.xml", ErrorContext => 2);
# TODO: Update websites (esp. with new MPs)
$twig->parsefile($config::pwmembers . 'websites.xml', ErrorContext => 2);

# Get any data from the database
my $dbh = DBI->connect($config::dsn, $config::user, $config::pass, { RaiseError => 1, PrintError => 0 });
#DBI->trace(2);
makerankings($dbh);

# Now we are sure we have all the new data...
# Clean out old db
$dbh->prepare("delete from memberinfo")->execute();
$dbh->prepare("delete from personinfo")->execute();
$dbh->prepare("delete from consinfo")->execute();
my $memberinfoadd = $dbh->prepare("insert into memberinfo (member_id, data_key, data_value) values (?, ?, ?)");
my $personinfoadd = $dbh->prepare("insert into personinfo (person_id, data_key, data_value) values (?, ?, ?)");
my $consinfoadd = $dbh->prepare("insert into consinfo (constituency, data_key, data_value) values (?, ?, ?)");

# Write to database - members
foreach my $mp_id (keys %$memberinfohash)
{
        my $mp_id_num = $mp_id;
        $mp_id_num =~ s#uk.org.publicwhip/member/##;
        $mp_id_num =~ s#uk.org.publicwhip/lord/##;

        my $data = $memberinfohash->{$mp_id};
        foreach my $key (keys %$data)
        {
                my $value = $data->{$key};
                $memberinfoadd->execute($mp_id_num, $key, $value);
        }
}

# Write to database - people
foreach my $person_id (keys %$personinfohash)
{
        my $person_id_num = $person_id;
        $person_id_num =~ s#uk.org.publicwhip/person/##;

        my $data = $personinfohash->{$person_id};
        foreach my $key (keys %$data)
        {
                my $value = $data->{$key};
                $personinfoadd->execute($person_id_num, $key, $value);
        }
}

# Write to database - cons
foreach my $constituency (keys %$consinfohash)
{
        my $data = $consinfohash->{$constituency};
        foreach my $key (keys %$data)
        {
                my $value = $data->{$key};
                $consinfoadd->execute(encode_entities($constituency), $key, $value);
        }
}


# just temporary to check cron working
# print "mpinfoin done\n";

# Handler for loading data pertaining to a member id
sub loadmemberinfo
{ 
	my ($twig, $memberinfo) = @_;
	my $id = $memberinfo->att('id');
        foreach my $attname ($memberinfo->att_names())
        {
                next if $attname eq "id";
                my $value = $memberinfo->att($attname);
                $memberinfohash->{$id}->{$attname} = $value;
        }
}

# Handler for loading data pertaining to a person id
sub loadpersoninfo
{ 
	my ($twig, $personinfo) = @_;
	my $id = $personinfo->att('id');
        foreach my $attname ($personinfo->att_names())
        {
                next if $attname eq "id";
                my $value = $personinfo->att($attname);
                $personinfohash->{$id}->{$attname} = $value;
        }
}

# Handler for loading data pertaining to a canonical constituency name
sub loadconsinfo
{ 
	my ($twig, $consinfo) = @_;
	my $id = $consinfo->att('canonical');
        foreach my $attname ($consinfo->att_names())
        {
                next if $attname eq "canonical";
                my $value = $consinfo->att($attname);
                $consinfohash->{$id}->{$attname} = $value;
        }
}


# Handler for loading register of members interests
sub loadregmeminfo
{
	my ($twig, $regmem) = @_;
	my $id = $regmem->att('personid');

        my $htmlcontent = "";

	for (my $category = $regmem->first_child('category'); $category; 
		$category = $category->next_sibling('category'))
        {
                $htmlcontent .= '<div class="regmemcategory">';
                $htmlcontent .= $category->att("type") . ". " . $category->att("name");
                $htmlcontent .= "</div>\n";
                for (my $item = $category->first_child('item'); $item; 
                        $item = $item->next_sibling('item'))
                {
                        $htmlcontent .= '<div class="regmemitem">';
                        if ($item->att("subcategory"))
                        {
                                $htmlcontent .= "(" . $item->att("subcategory") . ") ";
                        }
                        $htmlcontent .= $item->sprint(1);
                        $htmlcontent .= "</div>\n";
                }
        }

        $personinfohash->{$id}->{"register_member_interests_html"} = $htmlcontent;
        $personinfohash->{$id}->{"register_member_interests_date"} = $regmem->att('date');
}

# Generate rankings of number of times spoken
sub makerankings {
        my $dbh = shift;
        
        # Loop through MPs
        my $query = "select member_id,person_id,entered_house,left_house from member 
                where person_id in ";
        my $sth = $dbh->prepare($query .
                #"( 10001 )");
                    '(select person_id from member where house=1 AND curdate() <= left_house) order by member_id');
        $sth->execute();
        if ($sth->rows == 0) {
                $sth = $dbh->prepare($query .
                        '(select person_id from member where left_house = \'2005-04-11\')');
                $sth->execute();
                if ($sth->rows == 0) {
                        print "Failed to find any MPs for rankings, change General Election date here if you are near one";
                        return;
                }
        }
        my %first_member;
        while ( my @row = $sth->fetchrow_array() ) 
        {
                my $mp_id = $row[0];
                my $person_id = $row[1];
                my $entered_house = $row[2];
                my $left_house = $row[3];
                my $fullid = "uk.org.publicwhip/member/$mp_id";
                my $person_fullid = "uk.org.publicwhip/person/$person_id";

                if ($entered_house eq '2005-05-05' && !$first_member{$person_id}) {
                        my $q = $dbh->prepare('select gid from hansard where major=1 and speaker_id=? order by hdate,hpos limit 1');
                        $q->execute($mp_id);
                        if ($q->rows > 0) {
                                my @row = $q->fetchrow_array();
                                my $maidenspeech = $row[0];
                                $personinfohash->{$person_fullid}->{'maiden_speech'} = $maidenspeech;
                        }
                }

                my $tth = $dbh->prepare("select count(*) from hansard, epobject
                        where hansard.epobject_id = epobject.epobject_id and speaker_id = ? and (major = 1 or major = 2) and 
                        hdate >= date_sub(curdate(), interval 1 year) and
                        body not like '%rose&#8212;%' group by section_id");
                my $rows = $tth->execute($mp_id);
                $personinfohash->{$person_fullid}->{"debate_sectionsspoken_inlastyear"} += int($rows);

                $tth = $dbh->prepare("
                       select count(*) from hansard, comments where hansard.epobject_id = comments.epobject_id and visible
                       and speaker_id = ?");
                $tth->execute($mp_id);
                my @thisrow = $tth->fetchrow_array();
                my $comments = $thisrow[0];
                $personinfohash->{$person_fullid}->{"comments_on_speeches"} += int($comments);

                $tth = $dbh->prepare("select count(*) from hansard where speaker_id = ? and major = 3 and minor = 1 and
                        hdate >= date_sub(curdate(), interval 1 year)
                                ");
                $tth->execute($mp_id);
                @thisrow = $tth->fetchrow_array();
                my $speeches = $thisrow[0];
                $personinfohash->{$person_fullid}->{"wrans_asked_inlastyear"} += $speeches;

                $tth = $dbh->prepare("select count(*) from hansard where speaker_id = ? and major = 3 and minor = 2 and 
                        hdate >= date_sub(curdate(), interval 1 year)");
                $tth->execute($mp_id);
                @thisrow = $tth->fetchrow_array();
                $speeches = $thisrow[0];
                $personinfohash->{$person_fullid}->{"wrans_answered_inlastyear"} += $speeches;

                if ($left_house eq '9999-12-31' && $memberinfohash->{$fullid}->{"swing_to_lose_seat"})
                {
                        $memberinfohash->{$fullid}->{"swing_to_lose_seat_today"} = $memberinfohash->{$fullid}->{"swing_to_lose_seat"};
                }

                $tth = $dbh->prepare("select count(*) as c, body from hansard as h1 
                                        left join epobject on h1.section_id = epobject.epobject_id 
                                        where h1.major = 3 and h1.minor = 
                                        1 and h1.speaker_id = ? group by body");
                $tth->execute($mp_id);
                while (my @row = $tth->fetchrow_array()) {
                        my $count = $row[0];
                        my $dept = $row[1];
                        $personinfohash->{$person_fullid}->{"wrans_departments"}->{$dept} = 0 if
                                !defined($personinfohash->{$person_fullid}->{"wrans_departments"}->{$dept});
                        $personinfohash->{$person_fullid}->{"wrans_departments"}->{$dept} += $count;
                }
                $tth = $dbh->prepare("select count(*) as c, body from hansard as h1 
                                        left join epobject on h1.subsection_id = epobject.epobject_id 
                                        where h1.major = 3 and h1.minor = 
                                        1 and h1.speaker_id = ? group by body");
                $tth->execute($mp_id);
                while (my @row = $tth->fetchrow_array()) {
                        my $count = $row[0];
                        my $subject = $row[1];
                        $personinfohash->{$person_fullid}->{"wrans_subjects"}->{$subject} = 0 if
                                !defined($personinfohash->{$person_fullid}->{"wrans_subjects"}->{$subject});
                        $personinfohash->{$person_fullid}->{"wrans_subjects"}->{$subject} += $count;
                }
 
                $tth = $dbh->prepare("select body from epobject,hansard where hansard.epobject_id = epobject.epobject_id and speaker_id=? and (major=1 or major=2)");
                $tth->execute($mp_id);
                $personinfohash->{$person_fullid}->{'three_word_alliterations'} = 0 if !$personinfohash->{$person_fullid}->{'three_word_alliterations'};
                my $words = 0; my $syllables = 0; my $sentences = 0;
                while (my @row = $tth->fetchrow_array()) {
                        my $body = $row[0];
                        $body =~ s/<\/p>/\n\n/g;
                        $body =~ s/<\/?p[^>]*>//g;
                        $body =~ s/ hon\. / honourable /g;
                        if ($body =~ m/\b((\w)\w*\s+\2\w*\s+\2\w*)\b/) {
                                $personinfohash->{$person_fullid}->{'three_word_alliterations'} += 1
                        }
                        
                        my @sent = split(/(?:(?<!Mr|St)(?<!Ltd)\.|!|\?)\s+/, $body);
                        $sentences += @sent;
                        for (split /\W+/, $body) {
                                $words++;
                                $syllables += syllable($_);
                        }
                }
                $personinfohash->{$person_fullid}->{'total_words'} = 0 if !$personinfohash->{$person_fullid}->{'total_words'};
                $personinfohash->{$person_fullid}->{'total_words'} += $words;
                $personinfohash->{$person_fullid}->{'total_sents'} = 0 if !$personinfohash->{$person_fullid}->{'total_sents'};
                $personinfohash->{$person_fullid}->{'total_sents'} += $sentences;
                $personinfohash->{$person_fullid}->{'total_sylls'} = 0 if !$personinfohash->{$person_fullid}->{'total_sylls'};
                $personinfohash->{$person_fullid}->{'total_sylls'} += $syllables;

                $first_member{$person_id} = 1;

                $tth = $dbh->prepare("select count(*) from moffice where person=? and source='chgpages/selctee' and to_date='9999-12-31'");
                $tth->execute($person_id);
                my $selctees = ($tth->fetchrow_array())[0];
                $personinfohash->{$person_fullid}->{'select_committees'} = $selctees if ($selctees);
                $tth = $dbh->prepare("select count(*) from moffice where person=? and source='chgpages/selctee' and to_date='9999-12-31' and position='Chairman'");
                $tth->execute($person_id);
                $selctees = ($tth->fetchrow_array())[0];
                $personinfohash->{$person_fullid}->{'select_committees_chair'} = $selctees if $selctees;
        }

        # Consolidate wrans departments and subjects, to pick top 5
        foreach (keys %$personinfohash) {
                my $key = $_;
                my $dept = $personinfohash->{$key}->{'wrans_departments'};
                if (defined($dept)) {
                        my @ordered = sort { $dept->{$b} <=> $dept->{$a} } keys %$dept;
                        @ordered = @ordered[0..4] if (scalar(@ordered) > 5);
                        $personinfohash->{$key}->{'wrans_departments'} = join(', ', @ordered);
                }
                my $subj = $personinfohash->{$key}->{'wrans_subjects'};
                if (defined($subj)) {
                        my @ordered = sort { $subj->{$b} <=> $subj->{$a} } keys %$subj;
                        @ordered = @ordered[0..4] if (scalar(@ordered) > 5);
                        $personinfohash->{$key}->{'wrans_subjects'} = join(', ', @ordered);
                }
                #$personinfohash->{$key}->{'reading_ease'} = -1;
                if ($personinfohash->{$key}->{'total_sents'} && $personinfohash->{$key}->{'total_words'}) {
                        $personinfohash->{$key}->{'reading_ease'} = 206.835
                                - 1.015 * ($personinfohash->{$key}->{'total_words'} / $personinfohash->{$key}->{'total_sents'})
                                - 84.6 * ($personinfohash->{$key}->{'total_sylls'} / $personinfohash->{$key}->{'total_words'});
                        $personinfohash->{$key}->{'reading_year'} = 1 -15.59
                                + 0.39 * ($personinfohash->{$key}->{'total_words'} / $personinfohash->{$key}->{'total_sents'})
                                + 11.8 * ($personinfohash->{$key}->{'total_sylls'} / $personinfohash->{$key}->{'total_words'});
                }
                delete $personinfohash->{$key}->{'total_words'};
                delete $personinfohash->{$key}->{'total_sylls'};
                delete $personinfohash->{$key}->{'total_sents'};
        }

        # Loop through Lords
        $query = "select member_id,person_id from member 
                where person_id in ";
        $sth = $dbh->prepare($query .
                '(select person_id from member where house=2 AND curdate() <= left_house) order by member_id');
        $sth->execute();
        while ( my @row = $sth->fetchrow_array() ) {
                my $mp_id = $row[0];
                my $person_id = $row[1];
                my $fullid = "uk.org.publicwhip/member/$mp_id";
                my $person_fullid = "uk.org.publicwhip/person/$person_id";

                my $tth = $dbh->prepare("select count(*) from hansard, epobject
                        where hansard.epobject_id = epobject.epobject_id and speaker_id = ? and major = 101 and 
                        hdate >= date_sub(curdate(), interval 1 year) and
                        body not like '%rose&#8212;%' group by section_id");
                my $rows = $tth->execute($mp_id);
                $personinfohash->{$person_fullid}->{"Ldebate_sectionsspoken_inlastyear"} += int($rows);

                $tth = $dbh->prepare("
                       select count(*) from hansard, comments where hansard.epobject_id = comments.epobject_id and visible
                       and speaker_id = ?");
                $tth->execute($mp_id);
                my @thisrow = $tth->fetchrow_array();
                my $comments = $thisrow[0];
                $personinfohash->{$person_fullid}->{"Lcomments_on_speeches"} += int($comments);

                $tth = $dbh->prepare("select count(*) from hansard where speaker_id = ? and major = 3 and minor = 1 and
                        hdate >= date_sub(curdate(), interval 1 year)
                                ");
                $tth->execute($mp_id);
                @thisrow = $tth->fetchrow_array();
                my $speeches = $thisrow[0];
                $personinfohash->{$person_fullid}->{"Lwrans_asked_inlastyear"} += $speeches;

                $tth = $dbh->prepare("select count(*) from hansard where speaker_id = ? and major = 3 and minor = 2 and 
                        hdate >= date_sub(curdate(), interval 1 year)");
                $tth->execute($mp_id);
                @thisrow = $tth->fetchrow_array();
                $speeches = $thisrow[0];
                $personinfohash->{$person_fullid}->{"Lwrans_answered_inlastyear"} += $speeches;

                $tth = $dbh->prepare("select body from epobject,hansard where hansard.epobject_id = epobject.epobject_id and speaker_id=? and major=101");
                $tth->execute($mp_id);
                $personinfohash->{$person_fullid}->{'Lthree_word_alliterations'} = 0 if !$personinfohash->{$person_fullid}->{'Lthree_word_alliterations'};
                while (my @row = $tth->fetchrow_array()) {
                        my $body = $row[0];
                        if ($body =~ m/\b((\w)\w*\s+\2\w*\s+\2\w*)\b/) {
                                $personinfohash->{$person_fullid}->{'Lthree_word_alliterations'} += 1
                        }
                }
        }

        for (my $year=2002; $year<=2005; ++$year) {
                foreach my $mp_id (keys %$personinfohash) {
                        if (defined($personinfohash->{$mp_id}->{'expenses'.$year.'_col1'})) {
                                my $total = 0; my $num;
                                for (my $col=1; $col<=9; ++$col) {
                                        $num = $personinfohash->{$mp_id}->{'expenses'.$year.'_col'.$col};
                                        $total += $num;
                                }
                                if ($year>=2004) {
                                        $num = $personinfohash->{$mp_id}->{'expenses'.$year.'_col7a'};
                                        $total += $num;
                                }
                                $personinfohash->{$mp_id}->{'expenses'.$year.'_total'} = $total;
                        }
                }
        }

        enrankify($personinfohash, "debate_sectionsspoken_inlastyear", 0);
        enrankify($personinfohash, "comments_on_speeches", 0);
        enrankify($personinfohash, "wrans_asked_inlastyear", 0);
        enrankify($personinfohash, "Ldebate_sectionsspoken_inlastyear", 0);
        enrankify($personinfohash, "Lcomments_on_speeches", 0);
        enrankify($personinfohash, "Lwrans_asked_inlastyear", 0);
        enrankify($personinfohash, "three_word_alliterations", 0);
        enrankify($personinfohash, "ending_with_a_preposition", 0);
        enrankify($personinfohash, "only_asked_why", 0);
        enrankify($personinfohash, "Lthree_word_alliterations", 0);
        enrankify($personinfohash, "Lending_with_a_preposition", 0);
        enrankify($memberinfohash, "swing_to_lose_seat_today", 0);
        enrankify($personinfohash, "reading_ease", 0);
        enrankify($personinfohash, "reading_year", 0);
        for (my $year=2002; $year<=2005; ++$year) {
                for (my $col=1; $col<=9; ++$col) {
                        enrankify($personinfohash, 'expenses'.$year.'_col'.$col, 0);
                }
                enrankify($personinfohash, 'expenses'.$year.'_col7a', 0) if ($year>=2004);
                enrankify($personinfohash, 'expenses'.$year.'_total', 0);
        }
        enrankify($personinfohash, "writetothem_responsiveness_mean_2005", 0);
}

# Generate ranks from a data field
sub enrankify
{
        my $hash = shift;
        my $field = shift;
        my $backwards = shift;

        # Extract value of $field for each MP who has it
        my @mps;
        my %mpsvalue;
        foreach my $mp_id (keys %$hash)
        {
                my $value = $hash->{$mp_id}->{$field};
                if (defined $value)
                {
                        $value =~ s/%//; # remove % from end
                        push @mps, $mp_id;
                        $mpsvalue{$mp_id} = $value;
                }
        }
        my @quintile = ();
        for (my $i=1; $i<=5; $i++) {
                $quintile[$i-1] = (@mps+1) * $i / 5;
        }

        # Sort, and calculate ranking for
        if ($backwards) {
                @mps = sort { $mpsvalue{$a} <=> $mpsvalue{$b} } @mps;
        } else {
                @mps = sort { $mpsvalue{$b} <=> $mpsvalue{$a} } @mps;
        }
        my %mpvaluerank;
        my $rank = 0;
        my $activerank = 0;
        my $prevvalue = -1;
        my $quintile = 0;
        for my $mp (@mps)
        {
                $rank++;
                $activerank = $rank if ($mpsvalue{$mp} != $prevvalue);
                $quintile++ if ($activerank>$quintile[$quintile]);
                #print $field . " " . $mp . " value $activerank of " . $#mps . "\n";
                $hash->{$mp}->{$field . "_rank"} = $activerank;
                $hash->{$mp}->{$field . "_rank_outof"} = scalar @mps;
                $hash->{$mp}->{$field . '_quintile'} = $quintile;
                $prevvalue = $mpsvalue{$mp};
        }
}

