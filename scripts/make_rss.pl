#!/usr/bin/perl

use warnings;
use strict;
use FindBin;
use lib "$FindBin::Bin";
use lib "$FindBin::Bin/../../perllib";

use XML::RSS;
use DBI;
use URI::Escape;

use mySociety::Config;
mySociety::Config::set_file('../conf/general');

my $dsn = 'DBI:mysql:database=' . mySociety::Config::get('DB_NAME'). ':host=' . mySociety::Config::get('DB_HOST');
my $dbh = DBI->connect($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASSWORD'), { RaiseError => 1, PrintError => 0 });

my $dc = {
    subject => '',
    creator => 'TheyWorkForYou.com',
    publisher => 'TheyWorkForYou.com',
    rights => 'Parliamentary Copyright',
    language => 'en-gb',
    ttl => 600
};

my $syn = {
    updatePeriod => 'daily',
    updateFrequency => '1',
    updateBase => '1901-01-01T00:00+00:00',
};

debates_rss(1, 'House of Commons debates', 'debates/', 'debates/debates.rss');
debates_rss(101, 'House of Lords debates', 'lords/', 'lords/lords.rss');
debates_rss(2, 'Westminster Hall debates', 'whall/', 'whall/whall.rss');
debates_rss(5, 'Northern Ireland Assembly debates', 'ni/', 'ni/ni.rss');
wms_rss();
# wrans_rss();
pbc_rss();

sub debates_rss {
    my ($major, $title, $url, $file) = @_;
    my $query = $dbh->prepare("select hdate from hansard where major=$major order by hdate desc limit 1");
    $query->execute();
    my ($date) = $query->fetchrow_array();
    return unless $date;

    # do we need to do something date related here?
    $query = $dbh->prepare("SELECT e.body, h.hdate, h.htype, h.gid, h.subsection_id, h.section_id, h.epobject_id FROM hansard h, epobject e WHERE h.major=$major AND htype=10 AND h.hdate='$date' AND h.epobject_id = e.epobject_id order by h.epobject_id");
    $query->execute;

    my $rss = new XML::RSS (version => '1.0');
    $rss->channel(
        title => $title,
        link => "http://www.theyworkforyou.com/$url",
        description => "$title via TheyWorkForYou.com - http://www.theyworkforyou.com/",
        dc => $dc,
        syn => $syn,
    );

    my $body = '';
    while (my $result = $query->fetchrow_hashref) {
        my ($id) = $result->{gid} =~ m#\/([^/]+)$#;
        $body .= "<li><a href=\"http://www.theyworkforyou.com/$url?id=$id\">$result->{body}</a></li>\n";
    }
    $rss->add_item(
        title => "$title for $date",
        link => "http://www.theyworkforyou.com/$url?d=$date",
        description => "<ul>\n\n$body\n\n</ul>\n"
    );

    open(FP, '>' . mySociety::Config::get('BASEDIR') . "/$file") or die $!;
    print FP $rss->as_string;
    close FP;
}

sub wms_rss {
    my $query = $dbh->prepare("select hdate from hansard where major=4 order by hdate desc limit 1");
    $query->execute();
    my ($date) = $query->fetchrow_array();
    return unless $date;

    $query = $dbh->prepare("
        SELECT e.body, h.hdate, h.htype, h.gid, h.subsection_id, h.section_id,
                h.epobject_id, m.house, m.title, m.first_name, m.last_name, m.constituency, m.person_id
        FROM hansard h, epobject e, member m
        WHERE h.major=4 AND htype=12 AND h.hdate='$date' AND section_id != 0 AND subsection_id != 0
        AND h.epobject_id = e.epobject_id AND h.speaker_id = m.member_id
        ORDER BY h.epobject_id desc");
    $query->execute;

    my $rss = new XML::RSS (version => '1.0');
    $rss->channel(
        title => "Written Ministerial Statements",
        link => "http://www.theyworkforyou.com/wms/",
        description => "Written Ministerial Statements via TheyWorkForYou.com - http://www.theyworkforyou.com/",
        dc => $dc,
        syn => $syn,
    );

    while (my $result = $query->fetchrow_hashref) {
        my $local_title_query = $dbh->prepare("select body from epobject where epobject_id=$result->{subsection_id}");
        $local_title_query->execute;
        my ($title) = $local_title_query->fetchrow_array; # title, not dept.
        my $local_office_query = $dbh->prepare('SELECT position,dept FROM moffice WHERE person=' .$result->{person_id} . ' ORDER BY from_date DESC LIMIT 1');
        $local_office_query->execute;
        my ($posn, $dept) = $local_office_query->fetchrow_array;
        $title .= ' (' . member_full_name($result);
        $title .= ", $posn, $dept" if ($posn && $dept);
        $title .= ')';

        my ($id) = $result->{gid} =~ m#\/([^/]+)$#;
        $rss->add_item(
            title => $title,
            link => 'http://www.theyworkforyou.com/wms/?id=' . $id,
            description => $result->{body}
        );
    }
    open (FP, '>' . mySociety::Config::get('BASEDIR') . "/wms/wms.rss") or die $!;
    print FP $rss->as_string;
    close FP;
}

sub wrans_rss {
    my $query = $dbh->prepare("select hdate from hansard where major='3' order by hdate desc limit 1");
    $query->execute();
    my ($date) = $query->fetchrow_array();

    $query = $dbh->prepare("SELECT e.body, h.hdate, h.htype, h.gid, h.subsection_id, h.section_id, h.epobject_id FROM hansard h, epobject e WHERE h.major='3' AND htype='12' AND hdate='$date' AND h.epobject_id = e.epobject_id order by h.epobject_id desc");
    $query->execute;
    my $rss = new XML::RSS (version => '1.0');
    $rss->channel(
        title => "Written Answers",
        link => "http://www.theyworkforyou.com/wrans/",
        description => "Written Answers via TheyWorkForYou.com- http://www.theyworkforyou.com/ .",
        dc => $dc,
        syn => $syn,
    );

    while (my $result = $query->fetchrow_hashref) {
        my $local_title_query = $dbh->prepare("select body from epobject where epobject_id=$result->{subsection_id}");
        $local_title_query->execute;
        my ($title) = $local_title_query->fetchrow_array; # title, not dept.
        my ($id) = $result->{gid} =~ m#\/([^/]+)$#;

        next unless ($id =~ /q\d+$/);

        $rss->add_item(
            title => $title,
            link => 'http://www.theyworkforyou.com/wrans/?id=' . $id,
            description => $result->{body}
        );
    }
    open (FP, '>' . mySociety::Config::get('BASEDIR') . "/wrans/wrans.rss") or die $!;
    print FP $rss->as_string;
    close FP;
}

sub pbc_rss {
    my $query = $dbh->selectall_arrayref('select gid, minor, hdate from hansard
        where htype=10 and major=6
        order by hdate desc limit 20');
    my $rss = new XML::RSS (version => '1.0');
    $rss->channel(
        title => "Public Bill Committee debates",
        link => "http://www.theyworkforyou.com/pbc/",
        description => "Public Bill Committee debates via TheyWorkForYou.com- http://www.theyworkforyou.com/ .",
        dc => $dc,
        syn => $syn,
    );
    foreach (@$query) {
        my ($gid, $minor, $hdate) = @$_;
        my ($title, $session) = $dbh->selectrow_array('select title, session from bills where id=?', {}, $minor);
        $gid =~ /standing\d\d\d\d-\d\d-\d\d_.*?_(\d\d)-\d_\d\d\d\d-\d\d-\d\d/;
        my $sitting = ordinal($1+0);
        my $u_title = uri_escape($title);
        $u_title =~ s/%20/+/g;
        $rss->add_item(
                title => "$title, $sitting sitting",
                link => "http://www.theyworkforyou.com/pbc/$session/$u_title",
                #description => $result->{body
        );
    }
    open (FP, '>' . mySociety::Config::get('BASEDIR') . "/pbc/pbc.rss") or die $!;
    print FP $rss->as_string;
    close FP;
}

sub member_full_name {
    my $result = shift;
    my $house = $result->{house};
    my $title = $result->{title};
    my $first_name = $result->{first_name};
    my $last_name = $result->{last_name};
    my $con = $result->{constituency};
    my $s = 'ERROR';
    if ($house == 1) {
        $s = $first_name . ' ' . $last_name;
        if ($title) {
            $s = $title . ' ' . $s;
        }
    } elsif ($house == 2) {
        $s = '';
        $s = 'The ' if (!$last_name);
        $s .= $title;
        $s .= ' ' . $last_name if $last_name;
        $s .= ' of ' . $con if $con;
    }
    return $s;
}

sub ordinal {
    return $_[0] . ordsuf($_[0]);
}
sub ordsuf {
    my $n = shift;
    $n %= 100;
    return 'th' if $n >= 11 && $n <= 13;
    $n %= 10;
    return 'st' if $n == 1;
    return 'nd' if $n == 2;
    return 'rd' if $n == 3;
    return 'th';
}
