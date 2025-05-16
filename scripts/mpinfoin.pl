#! /usr/bin/perl -w

use strict;

# Reads XML files with info about MPs and constituencies into the database

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
use JSON;
use File::Slurp;

my %action;
my $verbose;
my $action_count = 0;
foreach (@ARGV) {
    if ($_ eq 'links') {
        $action{'links'} = 1;
        $action_count++;
    } elsif ($_ eq 'compile') {
        $action{'compile'} = 1;
        $action_count++;
    } elsif ($_ eq 'eu_ref_position') {
        $action{'eu_ref_position'} = 1;
        $action_count++;
    } elsif ($_ eq 'verbose') {
        $verbose = 1;
    } else {
        print "Action '$_' not known\n";
        exit(0);
    }
}
if ($action_count == 0) {
    $action{'links'} = 1;
    $action{'compile'} = 1;
    $action{'eu_ref_position'} = 1;
}

# Fat old hashes intotwixt all the XML is loaded and colated before being squirted to the DB
my $memberinfohash;
my $personinfohash;
my $consinfohash;

# Read in all the files
my $twig = XML::Twig->new(
    twig_handlers => {
        'memberinfo' => \&loadmemberinfo,
        'personinfo' => \&loadpersoninfo,
        'consinfo' => \&loadconsinfo,
    }, output_filter => 'safe' );



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
    print "  MPs standing down\n" if $verbose;
    $twig->parsefile($pwmembers . "wikipedia-standingdown.xml", ErrorContext => 2);
    print "  Bishops\n" if $verbose;
    $twig->parsefile($pwmembers . "diocese-bishops.xml", ErrorContext => 2);
    print "  BBC\n" if $verbose;
    $twig->parsefile($pwmembers . "bbc-links.xml", ErrorContext => 2);
    print "  BBC IDs\n" if $verbose;
    $twig->parsefile($pwmembers . "bbc-constituency-ids.xml", ErrorContext => 2);
    print "  PA/Guardian constituency IDs\n" if $verbose;
    $twig->parsefile($pwmembers . "constituency-links.xml", ErrorContext => 2);
    print "  dates of birth\n" if $verbose;
    $twig->parsefile($pwmembers . "dates-of-birth.xml", ErrorContext => 2);
    # TODO: Update websites (esp. with new MPs)
    print "  Personal websites\n" if $verbose;
    $twig->parsefile($pwmembers . 'websites.xml', ErrorContext => 2);
    print "  MSP websites\n" if $verbose;
    $twig->parsefile($pwmembers . 'websites-sp.xml', ErrorContext => 2);
    print "  Twitter usernames and facebook pages\n" if $verbose;
    $twig->parsefile($pwmembers . 'twitter.xml', ErrorContext => 2);
    $twig->parsefile($pwmembers . 'social-media-commons.xml', ErrorContext => 2);
    $twig->parsefile($pwmembers . 'social-media-sp.xml', ErrorContext => 2);
    $twig->parsefile($pwmembers . 'social-media-ni.xml', ErrorContext => 2);
    print "  Official Parliamentary profiles\n" if $verbose;
    $twig->parsefile($pwmembers . 'official-profiles.xml', ErrorContext => 2);
    chdir $FindBin::Bin;
    print "  Lords biographies\n" if $verbose;
    $twig->parsefile($pwmembers . 'lordbiogs.xml', ErrorContext => 2);
}


if ($action{'eu_ref_position'}) {
    my $positions = decode_json(scalar read_file($pwmembers . 'eu_ref_positions.json'));
    foreach my $id (keys(%{$positions})) {
        $personinfohash->{$id}->{'eu_ref_stance'} = $positions->{$id};
    }
}

# Get any data from the database
my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0, , mysql_enable_utf8 => 1 });
#DBI->trace(2);
if ($action{'compile'}) {
    print "Compiling information\n" if $verbose;
    compile_info($dbh);
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


# Set AutoCommit off
$dbh->{AutoCommit} = 0;

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
$dbh->commit();

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
$dbh->commit();

# Write to database - cons
foreach my $constituency (keys %$consinfohash) {
    my $data = $consinfohash->{$constituency};
    foreach my $key (keys %$data) {
        my $value = $data->{$key};
        $consinfoadd->execute($constituency, $key, $value, $value);
    }
}
$dbh->commit();


# Set AutoCommit on
$dbh->{AutoCommit} = 1;

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




sub commons_dissolved {
    return unless mySociety::Config::get('DISSOLUTION_DATE');
    my @houses = split /,/, mySociety::Config::get('DISSOLUTION_DATE');
    foreach (@houses) {
        my ($house, $date) = split /:/;
        return $date if $house == 1;
    }
}

sub compile_info {
    my $dbh = shift;

    # Loop through MPs
    my $query = "select person_id,entered_house,left_house from member where person_id in ";
    my $sth = $dbh->prepare($query .
        #"( 10001 )");
            '(select person_id from member where house=1 AND curdate() <= left_house) order by person_id, entered_house');
    $sth->execute();
    if ($sth->rows == 0 && commons_dissolved()) {
        $sth = $dbh->prepare($query .
            '(select person_id from member where left_house = ?)');
        $sth->execute(commons_dissolved());
        if ($sth->rows == 0) {
            print "Failed to find any MPs for compilation, change dissolution date if you are near one";
            return;
        }
    }
    my %first_member;
    while ( my @row = $sth->fetchrow_array() )
    {
        my $person_id = $row[0];
        my $entered_house = $row[1];
        my $left_house = $row[2];
        my $person_fullid = "uk.org.publicwhip/person/$person_id";

        my $q = $dbh->prepare("select gid from hansard, epobject
            where hansard.epobject_id = epobject.epobject_id and major=1 and person_id=?
            and (body like '%maiden speech%' or body like '%first speech%' or body like '%predecessor%'
                or body like '%previous Member of Parliament%' or body like '%honour to have been elected%')
            order by hdate,hpos limit 1");
        $q->execute($person_id);
        if ($q->rows > 0) {
            my @row = $q->fetchrow_array();
            my $maidenspeech = $row[0];
            $personinfohash->{$person_fullid}->{'maiden_speech'} = $maidenspeech;
        }

        my $tth = $dbh->prepare("select count(*) as c, body from hansard as h1
                    left join epobject on h1.section_id = epobject.epobject_id
                    where h1.major = 3 and h1.minor =
                    1 and h1.person_id = ? group by body");
        $tth->execute($person_id);
        while (my @row = $tth->fetchrow_array()) {
            my $count = $row[0];
            my $dept = $row[1];
            $personinfohash->{$person_fullid}->{"wrans_departments"}->{$dept} = $count;
        }

        $tth = $dbh->prepare("select count(*) as c, body from hansard as h1
                    left join epobject on h1.subsection_id = epobject.epobject_id
                    where h1.major = 3 and h1.minor =
                    1 and h1.person_id = ? group by body");
        $tth->execute($person_id);
        while (my @row = $tth->fetchrow_array()) {
            my $count = $row[0];
            my $subject = $row[1];
            $personinfohash->{$person_fullid}->{"wrans_subjects"}->{$subject} = $count;
        }
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
    }
}
