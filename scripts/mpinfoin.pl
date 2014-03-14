#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;

# $Id: mpinfoin.pl,v 1.50 2010-01-28 09:21:17 matthew Exp $

# Reads XML files with info about MPs and constituencies into
# the memberinfo table of the fawkes DB

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../commonlib/perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");
my $pwmembers = mySociety::Config::get('PWMEMBERS');

use XML::Twig;
use DBI;
use File::Find;
use LWP::UserAgent;
use HTML::Entities;
use Data::Dumper;
use Syllable;

my %action;
my $verbose;
foreach (@ARGV) {
        if ($_ eq 'publicwhip') {
                $action{'pw'} = 1;
        } elsif ($_ eq 'expenses') {
                $action{'expenses'} = 1;
        } elsif ($_ eq 'regmem') {
                $action{'regmem'} = 1;
        } elsif ($_ eq 'links') {
                $action{'links'} = 1;
        } elsif ($_ eq 'writetothem') {
                $action{'wtt'} = 1;
        } elsif ($_ eq 'rankings') {
                $action{'rankings'} = 1;
        } elsif ($_ eq 'speaker_candidates') {
                $action{'speaker_candidates'} = 1;
        } elsif ($_ eq 'verbose') {
                $verbose = 1;
        } else {
                print "Action '$_' not known\n";
                exit(0);
        }
}
if (scalar(@ARGV) == 0) {
        $action{'pw'} = 1;
        $action{'expenses'} = 1;
        $action{'regmem'} = 1;
        $action{'links'} = 1;
        $action{'wtt'} = 1;
        $action{'rankings'} = 1;
        $action{'speaker_candidates'} = 1;
}

# Fat old hashes intotwixt all the XML is loaded and colated before being squirted to the DB
my $memberinfohash;
my $personinfohash;
my $consinfohash;

# Find latest register of members interests file
chdir mySociety::Config::get('RAWDATA');
my $regmemfile = "";
find sub { $regmemfile = $_ if /^regmem.*\.xml$/ and $_ ge $regmemfile}, 'scrapedxml/regmem/';

# Read in all the files
my $twig = XML::Twig->new(
        twig_handlers => {
                'memberinfo' => \&loadmemberinfo,
                'personinfo' => \&loadpersoninfo,
                'consinfo' => \&loadconsinfo,
                'speakercandidateinfo' => \&loadspeakercandidateinfo,
                'regmem' => \&loadregmeminfo
        }, output_filter => 'safe' );

if ($action{'regmem'}) {
        # TODO: Parse ALL regmem in forwards chronological order, so each MP (even ones left parl) gets their most recent one
        print "Parsing register of members' interests\n" if $verbose;
        $twig->parsefile(mySociety::Config::get('RAWDATA') . "scrapedxml/regmem/$regmemfile", ErrorContext => 2);
}

if ($action{'links'}) {
        print "Parsing links\n" if $verbose;
        print "  MLA Wikipedia\n" if $verbose;
        $twig->parsefile($pwmembers . "wikipedia-mla.xml", ErrorContext => 2);
        print "  MSP \"\n" if $verbose;
        $twig->parsefile($pwmembers . "wikipedia-msp.xml", ErrorContext => 2);
        print "  MP \"\n" if $verbose;
        $twig->parsefile($pwmembers . "wikipedia-commons.xml", ErrorContext => 2);
        print "  Lords \"\n" if $verbose;
        $twig->parsefile($pwmembers . "wikipedia-lords.xml", ErrorContext => 2);
        #print "  MPs standing down\n" if $verbose;
        #$twig->parsefile($pwmembers . "wikipedia-standingdown.xml", ErrorContext => 2);
        print "  Bishops\n" if $verbose;
        $twig->parsefile($pwmembers . "diocese-bishops.xml", ErrorContext => 2);
        print "  EDMs\n" if $verbose;
        $twig->parsefile($pwmembers . "edm-links.xml", ErrorContext => 2);
        print "  BBC\n" if $verbose;
        $twig->parsefile($pwmembers . "bbc-links.xml", ErrorContext => 2);
        print "  BBC IDs\n" if $verbose;
        $twig->parsefile($pwmembers . "bbc-constituency-ids.xml", ErrorContext => 2);
        print "  PA/Guardian constituency IDs\n" if $verbose;
        $twig->parsefile($pwmembers . "constituency-links.xml", ErrorContext => 2);
        print "  dates of birth\n" if $verbose;
        $twig->parsefile($pwmembers . "dates-of-birth.xml", ErrorContext => 2);
        # TODO: Update Guardian links
        print "  Guardian\n" if $verbose;
        $twig->parsefile($pwmembers . "guardian-links.xml", ErrorContext => 2);
        # TODO: Update websites (esp. with new MPs)
        print "  Personal websites\n" if $verbose;
        $twig->parsefile($pwmembers . 'websites.xml', ErrorContext => 2);
        print "  MSP websites\n" if $verbose;
        $twig->parsefile($pwmembers . 'websites-sp.xml', ErrorContext => 2);
        print "  MSP Twitter username\n" if $verbose;
        $twig->parsefile($pwmembers . 'twitter.xml', ErrorContext => 2);
        chdir $FindBin::Bin;
        print "  Lords biographies\n" if $verbose;
        $twig->parsefile($pwmembers . 'lordbiogs.xml', ErrorContext => 2);
        print "  Journalisted\n" if $verbose;
        $twig->parsefile($pwmembers . 'journa-list.xml', ErrorContext => 2);
}

if ($action{'wtt'}) {
        print "Parsing WTT stats\n" if $verbose;
        $twig->parseurl("http://www.writetothem.com/stats/2005/mps?xml=1");
        $twig->parseurl("http://www.writetothem.com/stats/2006/mps?xml=1");
        $twig->parseurl("http://www.writetothem.com/stats/2007/mps?xml=1");
        $twig->parseurl("http://www.writetothem.com/stats/2008/mps?xml=1");
}


if ($action{'speaker_candidates'}) {
        print "Parsing speaker candidates\n" if $verbose;
        $twig->parsefile($pwmembers . 'speaker-candidates.xml', ErrorContext => 2);
}


if ($action{'pw'}) {
        my $ua = LWP::UserAgent->new( agent => 'mySociety/1.0 (TheyWorkForYou)' );
        print "Parsing Public Whip attendance and policies\n" if $verbose;
        $twig->parseurl("http://www.publicwhip.org.uk/feeds/mp-info.xml", $ua);
        $twig->parseurl("http://www.publicwhip.org.uk/feeds/mp-info.xml?house=lords", $ua);
        # Various policy IDs, see http://www.publicwhip.org.uk/policies.php for what they are
        foreach my $dreamid (
            363,
            811,
            826,
            837,
            975,
            984,
            996,
            1027,
            1030,
            1049,
            1050,
            1051,
            1052,
            1053,
            1065,
            1071,
            1074,
            1079,
            1084,
            1087,
            1109,
            1110,
            1113,
            1120,
            1124,
            1132,
            1136,
            6670,
            6671,
            6672,
            6673,
            6674,
            6676,
            6677,
            6678,
            6679,
            6680,
            6681,
            6682,
            6683,
            6684,
            6686,
            6687,
            6688,
            6690,
            6691,
            6692,
            6693,
            6694,
            6696,
            6697,

        ) {
          $twig->parseurl("http://www.publicwhip.org.uk/feeds/mpdream-info.xml?id=$dreamid", $ua);
        }
}

if ($action{'expenses'}) {
        print "Parsing expenses\n" if $verbose;
        $twig->parsefile($pwmembers . "expenses200809.xml", ErrorContext => 2);
        $twig->parsefile($pwmembers . "expenses200708.xml", ErrorContext => 2);
        $twig->parsefile($pwmembers . "expenses200607.xml", ErrorContext => 2);
        $twig->parsefile($pwmembers . "expenses200506.xml", ErrorContext => 2);
        $twig->parsefile($pwmembers . "expenses200506former.xml", ErrorContext => 2);
        $twig->parsefile($pwmembers . "expenses200405.xml", ErrorContext => 2);
        $twig->parsefile($pwmembers . "expenses200304.xml", ErrorContext => 2);
        $twig->parsefile($pwmembers . "expenses200203.xml", ErrorContext => 2);
        $twig->parsefile($pwmembers . "expenses200102.xml", ErrorContext => 2);
        makerankings_expenses();
}

# Get any data from the database
my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0 });
#DBI->trace(2);
if ($action{'rankings'}) {
        print "Making rankings\n" if $verbose;
        makerankings($dbh);
}

# XXX: Will only ever add/update data now - need way to remove without dropping whole table...

# Now we are sure we have all the new data...
my $memberinfocheck  = $dbh->prepare('select data_value from memberinfo where member_id=? and data_key=?');
my $memberinfoadd    = $dbh->prepare("insert into memberinfo (member_id, data_key, data_value) values (?, ?, ?)");
my $memberinfoupdate = $dbh->prepare('update memberinfo set data_value=? where member_id=? and data_key=?');
my $personinfocheck  = $dbh->prepare('select data_value from personinfo where person_id=? and data_key=?');
my $personinfoadd    = $dbh->prepare("insert into personinfo (person_id, data_key, data_value) values (?, ?, ?)");
my $personinfoupdate = $dbh->prepare('update personinfo set data_value=? where person_id=? and data_key=?');
my $consinfoadd      = $dbh->prepare("insert into consinfo (constituency, data_key, data_value) values (?, ?, ?) on duplicate key update data_value=?");

# Write to database - members
foreach my $mp_id (keys %$memberinfohash) {
        (my $mp_id_num = $mp_id) =~ s#uk.org.publicwhip/(member|lord)/##;
        my $data = $memberinfohash->{$mp_id};
        foreach my $key (keys %$data) {
                my $new_value = $data->{$key};
                my $curr_value = $dbh->selectrow_array($memberinfocheck, {}, $mp_id_num, $key);
                if (!defined $curr_value) {
                        $memberinfoadd->execute($mp_id_num, $key, $new_value);
                } elsif ($curr_value ne $new_value) {
                        $memberinfoupdate->execute($new_value, $mp_id_num, $key);
                }
        }
}

# Write to database - people
foreach my $person_id (keys %$personinfohash) {
        (my $person_id_num = $person_id) =~ s#uk.org.publicwhip/person/##;
        my $data = $personinfohash->{$person_id};
        foreach my $key (keys %$data) {
                my $new_value = $data->{$key};
                my $curr_value = $dbh->selectrow_array($personinfocheck, {}, $person_id_num, $key);
                if (!defined $curr_value) {
                        $personinfoadd->execute($person_id_num, $key, $new_value);
                } elsif ($curr_value ne $new_value) {
                        $personinfoupdate->execute($new_value, $person_id_num, $key);
                }
        }
}

# Write to database - cons
foreach my $constituency (keys %$consinfohash) {
        my $data = $consinfohash->{$constituency};
        $constituency = Encode::encode('iso-8859-1', $constituency);
        foreach my $key (keys %$data) {
                my $value = $data->{$key};
                $consinfoadd->execute($constituency, $key, $value, $value);
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

# Handler for loading data pertaining to a speaker candidate
sub loadspeakercandidateinfo
{
        my ($twig, $speakerinfo) = @_;
        my $id = $speakerinfo->att('id');
        foreach my $attname ($speakerinfo->att_names())
        {
                next if $attname eq "id";
                my $value = $speakerinfo->att($attname);
                $personinfohash->{$id}->{$attname} = $value;
        }
        my $speaker_candidate_response = $speakerinfo->first_child('speakercandidateresponse');
        if ($speaker_candidate_response){
            $personinfohash->{$id}->{'speaker_candidate_response'} = $speaker_candidate_response->xml_string();
            my $speaker_candidate_response_summary = $speakerinfo->first_child('speakercandidateresponsesummary');
            $personinfohash->{$id}->{'speaker_candidate_response_summary'} = $speaker_candidate_response_summary->xml_string();
        } else {
            $personinfohash->{$id}->{'speaker_candidate_response'} = "";
            $personinfohash->{$id}->{'speaker_candidate_response_summary'} = "";
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
                    '(select person_id from member where house=1 AND curdate() <= left_house) order by person_id, entered_house');
        $sth->execute();
        if ($sth->rows == 0) {
                $sth = $dbh->prepare($query .
                        '(select person_id from member where left_house = \'2010-04-12\')');
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

                if (!$first_member{$person_id}) {
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
                $personinfohash->{$person_fullid}->{'three_word_alliteration_content'} = "" if !$personinfohash->{$person_fullid}->{'three_word_alliteration_content'};
                my $words = 0; my $syllables = 0; my $sentences = 0;
                while (my @row = $tth->fetchrow_array()) {
                        my $body = $row[0];
                        $body =~ s/<\/p>/\n\n/g;
                        $body =~ s/<\/?p[^>]*>//g;
                        $body =~ s/ hon\. / honourable /g;
                        if ($body =~ m/\b((\w)\w*\s+\2\w*\s+\2\w*)\b/) {
                                $personinfohash->{$person_fullid}->{'three_word_alliterations'} += 1;
                                $personinfohash->{$person_fullid}->{'three_word_alliteration_content'} .= ":$1";
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
                $personinfohash->{$person_fullid}->{'select_committees'} = $selctees;
                $tth = $dbh->prepare("select count(*) from moffice where person=? and source='chgpages/selctee' and to_date='9999-12-31' and position='Chairman'");
                $tth->execute($person_id);
                $selctees = ($tth->fetchrow_array())[0];
                $personinfohash->{$person_fullid}->{'select_committees_chair'} = $selctees;
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
        enrankify($personinfohash, "writetothem_responsiveness_mean_2005", 0);
}

sub makerankings_expenses {
        foreach my $mp_id (keys %$personinfohash) {
                if (defined($personinfohash->{$mp_id}->{'expenses2007_col5a'})) {
                        my $total = 0;
                        foreach my $let ('a'..'f') {
                                $total += $personinfohash->{$mp_id}->{'expenses2007_col5'.$let};
                        }
                        $personinfohash->{$mp_id}->{'expenses2007_col5'} = $total;
                }
        }

        for (my $year=2002; $year<=2007; ++$year) {
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

        foreach my $mp_id (keys %$personinfohash) {
            for (my $year=2008; $year<=2009; $year++) {
                my $prefix = "expenses$year";
                if (defined($personinfohash->{$mp_id}->{$prefix . '_colmp_reg_travel_a'})) {

                        my $total = 0;
                        foreach my $let ('a'..'d') {
                                $total += $personinfohash->{$mp_id}->{$prefix . '_colmp_reg_travel_'.$let};
                                $total += $personinfohash->{$mp_id}->{$prefix . '_colmp_other_travel_'.$let};
                        }
                        $personinfohash->{$mp_id}->{$prefix . '_col5'} = $total;
                        $personinfohash->{$mp_id}->{$prefix . '_col6'} = $personinfohash->{$mp_id}->{$prefix . '_colemployee_travel_a'};
                        $personinfohash->{$mp_id}->{$prefix . '_total'} = $personinfohash->{$mp_id}->{$prefix . '_coltotal_inc_travel'}
                            if $personinfohash->{$mp_id}->{$prefix . '_coltotal_inc_travel'};
                }
            }
        }

        for (my $year=2002; $year<=2009; ++$year) {
                next if $year == 2006;
                for (my $col=1; $col<=9; ++$col) {
                        enrankify($personinfohash, 'expenses'.$year.'_col'.$col, 0);
                }
                enrankify($personinfohash, 'expenses'.$year.'_col7a', 0) if ($year>=2004);
                enrankify($personinfohash, 'expenses'.$year.'_total', 0);
        }
        foreach my $let ('a'..'f') {
                enrankify($personinfohash, 'expenses2007_col5'.$let, 0);
        }

        foreach my $let ('a'..'d') {
            for (my $year=2008; $year<=2009; $year++) {
                enrankify($personinfohash, 'expenses' . $year . '_colmp_reg_travel_'.$let, 0);
                enrankify($personinfohash, 'expenses' . $year . '_colmp_other_travel_'.$let, 0);
            }
        }
        for (my $year=2008; $year<=2009; $year++) {
            enrankify($personinfohash, 'expenses' . $year . '_colcomms_allowance', 0);
            enrankify($personinfohash, 'expenses' . $year . '_colspouse_travel_a', 0);
            enrankify($personinfohash, 'expenses' . $year . '_colfamily_travel_a', 0);
            enrankify($personinfohash, 'expenses' . $year . '_coltotal_exc_travel', 0);
            enrankify($personinfohash, 'expenses' . $year . '_coltotal_travel', 0);
        }
        enrankify($personinfohash, 'expenses2009_colstationery', 0);
}

# Generate ranks from a data field
sub enrankify {
        my ($hash, $field, $backwards) = @_;

        # Extract value of $field for each MP who has it
        my (%mpsvalue, %valuecount);
        foreach my $mp_id (keys %$hash) {
                my $value = $hash->{$mp_id}->{$field};
                if (defined $value) {
                        $value =~ s/%//; # remove % from end
                        $mpsvalue{$mp_id} = $value;
                        $valuecount{$value}++;
                }
        }

        my $count = scalar keys %mpsvalue;
        return unless $count;

        # Sort, and calculate ranking for
        my @mps;
        if ($backwards) {
                @mps = sort { $mpsvalue{$a} <=> $mpsvalue{$b} } keys %mpsvalue;
        } else {
                @mps = sort { $mpsvalue{$b} <=> $mpsvalue{$a} } keys %mpsvalue;
        }

        my @quintile = ();
        for (my $i=1; $i<=4; $i++) {
                my $q = ($count + 1) * $i / 5;
                #$quintile[$i-1] = $q;
                $quintile[$i-1] = $mpsvalue{$mps[int($q)]}; # ceil
        }

        my $rank = 0;
        my $activerank = 0;
        my $prevvalue = -1;
        foreach my $mp (@mps) {
                $rank++;
                $activerank = $rank if ($mpsvalue{$mp} != $prevvalue);
                my $quintile;
                if ($backwards) {
                        # copy the below if you ever enrankify() something that is backwards
                } else {
                        # Ever so slightly biased towards average and above average, I guess
                        if ($mpsvalue{$mp} <= $quintile[1] && $mpsvalue{$mp} >= $quintile[2]) {
                                $quintile = 2;
                        } elsif ($mpsvalue{$mp} <= $quintile[0] && $mpsvalue{$mp} >= $quintile[1]) {
                                $quintile = 1;
                        } elsif ($mpsvalue{$mp} <= $quintile[2] && $mpsvalue{$mp} >= $quintile[3]) {
                                $quintile = 3;
                        } elsif ($mpsvalue{$mp} >= $quintile[0]) {
                                $quintile = 0;
                        } elsif ($mpsvalue{$mp} <= $quintile[3]) {
                                $quintile = 4;
                        } else {
                                die $!;
                        }
                }
                #print "$rank $activerank $mpsvalue{$mp} $quintile\n";
                #$quintile++ if ($activerank>$quintile[$quintile]);
                #print $field . " " . $mp . " value $activerank of " . $#mps . "\n";
                $hash->{$mp}->{$field . "_rank"} = $activerank;
                $hash->{$mp}->{$field . "_rank_joint"} = $valuecount{$mpsvalue{$mp}} if $valuecount{$mpsvalue{$mp}} > 1;
                $hash->{$mp}->{$field . "_rank_outof"} = $count;
                $hash->{$mp}->{$field . '_quintile'} = $quintile;
                $prevvalue = $mpsvalue{$mp};
        }
}

