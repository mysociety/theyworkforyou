#!/usr/bin/perl -I ../../perllib/ -w
use strict;
use lib "loader/";

my $mailshot_name = 'email_3';
my $test_email = "";
my $type = "all";
my $dryrun = 1;

$test_email = 'francis@flourish.org';
$test_email = 'frabcus@fastmail.fm';

my $amount = 1000000;

use DBI;
use URI::Escape;
use Text::CSV;
use Data::Dumper;

use mySociety::Config;

use FindBin;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASSWORD'), { RaiseError => 1, PrintError => 0 });

# Read in the dump file of EDM signature etc.
my $consvals = {};
my $csv = Text::CSV_XS->new ({ binary  => 1 });
open (CSV, "<", "../../../dumps/EDMsigned.csv") or die $!;
while (<CSV>) {
    if ($csv->parse($_)) {
        my ($pid,$name,$party,$constituency,$signed_2141,$modcom,$minister) = $csv->fields();
        $consvals->{$constituency}->{pid} = $pid;
        $consvals->{$constituency}->{name} = $name;
        $consvals->{$constituency}->{party} = $party;
        $consvals->{$constituency}->{constituency} = $constituency;
        $consvals->{$constituency}->{signed_2141} = $signed_2141;
        $consvals->{$constituency}->{modcom} = $modcom;
        $consvals->{$constituency}->{minister} = $minister;
    } else {
        my $err = $csv->error_input;
        print "Failed to parse line: $err";
    }
}
close CSV;
#        print Dumper($consvals);

# Extra where clause
my $where = "";
if ($test_email ne "") {
    $where = "and campaigners.email = '$test_email'";
}
my $already_clause = "
    left join campaigners_sent_email on 
        campaigners_sent_email.campaigner_id = campaigners.campaigner_id 
        and email_name = ?
    where email_name is null and confirmed";

# Create query string
my $query;
if ($type eq "all") {
    $query = "select campaigners.email, campaigners.token as token, 
        campaigners.campaigner_id as campaigner_id, 
        campaigners.postcode as postcode, campaigners.constituency as constituency
        from campaigners
        $already_clause $where group by campaigners.email";
} else {
    die "Choose type"
}
$query .= " limit $amount";

# Send mailshot
my $sth = $dbh->prepare($query);
$sth->execute($mailshot_name);
my $all = $sth->fetchall_hashref('email');
print "Sending to " . $sth->rows . " people\n";
foreach my $k (keys %$all)
{
    my $data = $all->{$k};

    my $email = $data->{'email'};
    my $campaigner_id = $data->{'campaigner_id'};
    my $token = $data->{'token'};
    my $constituency = $data->{'constituency'};
    my $postcode = $data->{'postcode'};
    my $realname = undef;
    my $url_postcode = uri_escape($postcode);
    my $mp_name = $consvals->{$constituency}->{name};

    my $to;
    if ($realname) {
        $realname =~ s/@/(at)/;
        $realname =~ s/,/ /;
        $to = $realname . " <" . $email . ">";
    } else {
        $to = $email;
    }

    print "Sending to $to";
    print "...";
    
    my $email_contents = "";

    $email_contents .= <<"EOF";
From: Free Our Bills <team\@theyworkforyou.com>
To: $to
EOF

    my $template_name = 'freeourbills_email_3_no.txt';
    my $template_file = "../www/includes/easyparliament/templates/emails/$template_name";
    open (TEXT, $template_file) or die "Can't open email template $template_file : $!";
    while (<TEXT>) {
            s/\$TOKEN/$token/g;
            s/\$POSTCODE/$postcode/g;
            s/\$URL_POSTCODE/$url_postcode/g;
            s/\$CONSTITUENCY/$constituency/g;
            s/\$MP_NAME/$mp_name/g;
            $email_contents .= $_;
    }

    if (!$consvals->{$constituency}->{signed_2141} &&
    !$consvals->{$constituency}->{modcom} &&
    !$consvals->{$constituency}->{minister}) {
        print " MP $mp_name... ";
        if (!$dryrun) {
            open(SENDMAIL, "|/usr/lib/sendmail -oi -t") or die "Can't fork for sendmail: $!\n";
            print SENDMAIL $email_contents;
            close(SENDMAIL) or die "sendmail didn't close nicely";
        } else {
            print "dry run... ";
        }
    } else {
        print " NOT!!!";
        print " signed_2141" if $consvals->{$constituency}->{signed_2141};
        print " modcom" if $consvals->{$constituency}->{modcom};
        print " minister" if $consvals->{$constituency}->{minister};
        print " MP $mp_name... ";
    }

    if (!$dryrun) {
        $dbh->do("insert into campaigners_sent_email (campaigner_id, email_name)
                values (?, ?)", {}, $campaigner_id, $mailshot_name);
    }

    print "done\n";

    sleep 0.1; # One second probably enough
}

