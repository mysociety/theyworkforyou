#!/usr/bin/perl -I ../../perllib/ -w
use strict;
use lib "loader/";

my $template_name = 'freeourbills_email_1.txt';
my $template_file = "../www/includes/easyparliament/templates/emails/$template_name";
my $test_email = "";

my $type = "all";

$test_email = 'francis@flourish.org';
$test_email = 'frabcus@fastmail.fm';

my $amount = 1000000;

use DBI;
use URI::Escape;
use mySociety::Config;
use FindBin;
mySociety::Config::set_file("$FindBin::Bin/../conf/general");
my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASSWORD'), { RaiseError => 1, PrintError => 0 });

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
$sth->execute($template_name);
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

    open(SENDMAIL, "|/usr/lib/sendmail -oi -t") or die "Can't fork for sendmail: $!\n";

    print SENDMAIL <<"EOF";
From: Free Our Bills <team\@theyworkforyou.com>
To: $to
EOF

    open (TEXT, $template_file) or die "Can't open email template $template_file : $!";
    while (<TEXT>) {
            s/\$TOKEN/$token/g;
            print SENDMAIL $_;
    }

    close(SENDMAIL) or die "sendmail didn't close nicely";

    $dbh->do("insert into campaigners_sent_email (campaigner_id, email_name)
            values (?, ?)", {}, $campaigner_id, $template_name);

    print "done\n";

    sleep 0.1; # One second probably enough
}

