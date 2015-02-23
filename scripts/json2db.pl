#! /usr/bin/perl -w
# vim:sw=8:ts=8:et:nowrap
use strict;
use utf8;

use FindBin;
chdir $FindBin::Bin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../commonlib/perllib";

use mySociety::Config;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $parldata = mySociety::Config::get('RAWDATA');
my $lastupdatedir = mySociety::Config::get('INCLUDESPATH') . "../../../xml2db/";

use DBI;
use HTML::Entities;
use File::Find;
use Getopt::Long;
use Data::Dumper;
use File::Slurp::Unicode;
use XML::Twig;

use Uncapitalise;
use JSON::XS;

use vars qw(%membertoperson);
my $json = JSON::XS->new->latin1;

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('TWFY_DB_NAME'). ':host=' . mySociety::Config::get('TWFY_DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('TWFY_DB_USER'), mySociety::Config::get('TWFY_DB_PASS'), { RaiseError => 1, PrintError => 0 });
my $motionadd = $dbh->prepare("INSERT INTO policydivisions (division_id, policy_id, house, division_title, division_date, division_number) VALUES (?, ?, ?, ?, ?, ?)");
my $voteadd = $dbh->prepare("INSERT INTO memberdivisionvotes (member_id, division_id, vote) VALUES (?, ?, ?)");

my $motionsdir = $parldata . "scrapedjson/policy-motions/";

add_mps_and_peers();

print Dumper \%membertoperson;

#$foo = decode_json( $json );
foreach my $dreamid (
    363,
    810,
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
    1105,
    1109,
    1110,
    1113,
    1120,
    1124,
    1132,
    1136,
    6667,
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
    6685,
    6686,
    6687,
    6688,
    6690,
    6691,
    6692,
    6693,
    6694,
    6695,
    6696,
    6697,
    6698,
    6699,
    6702,
    6703,
    6704,
    6705,
    6706,
    6707,
    6708,
    6709,
    6710,
    6711,
    6715,
    6716,
    6718,
    6719,
    6720,
    6721
) {
    my $policy_file = $motionsdir . $dreamid . ".json";
    my $policy_json = read_file($policy_file);
    my $policy = $json->decode($policy_json);
    my $motions = @{ $policy->{aspects} };

    for my $motion ( @{ $policy->{aspects} } ) {
        my ($motion_num, $house);
        ($motion_num = $motion->{motion}->{id}) =~ s/pw-\d+-\d+-\d+-(\d+)/$1/;
        ($house = $motion->{motion}->{organization_id}) =~ s/uk.parliament.(\w+)/$1/;

        $motionadd->execute($motion->{motion}->{id}, $dreamid, $house, $motion->{motion}->{text}, $motion->{motion}->{date}, $motion_num);

        for my $vote ( @{ $motion->{motion}->{ vote_events }->[0]->{votes} } ) {
            my $mp_id_num;
            $mp_id_num = $vote->{id};
            $mp_id_num = $membertoperson{$mp_id_num};
            $mp_id_num =~ s:uk.org.publicwhip/person/::;
            next unless $mp_id_num;

            $voteadd->execute($mp_id_num, $motion->{motion}->{id}, $vote->{option});
        }
    }
}


sub add_mps_and_peers {
    my $pwmembers = mySociety::Config::get('PWMEMBERS');
    my $twig = XML::Twig->new(twig_handlers =>
        { 'person' => \&loadperson },
        output_filter => 'safe' );
    $twig->parsefile($pwmembers . "people.xml");
}

sub loadperson {
    my ($twig, $person) = @_;
    my $curperson = $person->att('id');

    for (my $office = $person->first_child('office'); $office;
        $office = $office->next_sibling('office'))
    {
        $membertoperson{$office->att('id')} = $curperson;
    }
}
