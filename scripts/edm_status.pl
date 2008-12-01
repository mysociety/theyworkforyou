#!/usr/bin/perl

# edm_status.pl
# Looks up current status of our EDM, stores details for all MPs in a CSV file

use strict;
use warnings;
use WWW::Mechanize;
use HTML::TreeBuilder;
use LWP::Simple;

my $edm = get_edm_by_num(2141); # 2141 is our EDM

my $mps = get('http://www.theyworkforyou.com/api/getMPs?verbose=1&key=Gbr9QgCDzHExFzRwPWGAiUJ5');
$mps =~ s/ : / => /g; # Teehee
$mps = eval($mps);

print "PID,Name,Party,Constituency,Signed2141,ModCom,Minister\n";
my $count_check = 0;
foreach (@$mps) {
    my $mp = get('http://www.theyworkforyou.com/api/getMP?verbose=1&id=' . $_->{person_id} . '&key=Gbr9QgCDzHExFzRwPWGAiUJ5');
    $mp =~ s/ : / => /g;
    $mp = eval($mp);
    $mp = $mp->[0];
    my $signed = 0;
    if ($edm->{signatures}{$_->{name}}) {
        $signed = 1;
        delete $edm->{signatures}{$_->{name}};
    }
    $count_check++ if $signed;
    my $modcom = 0;
    my $minister = '';
    foreach my $off (@{$mp->{office}}) {
        my $source = $off->{source};
    if ($off->{dept} eq 'Modernisation of the House of Commons Committee') {
        $modcom = 1;
    }
    if ($source eq 'chgpages/govposts') {
        $minister = 'Minister';
    } elsif ($source eq 'chgpages/offoppose') {
        $minister = 'Tory min';
    } elsif ($source eq 'chgpages/libdem') {
        $minister = 'LD Min';
    } elsif ($source eq 'chgpages/privsec') {
        $minister = 'PPS';
    }
    }
    print "$_->{person_id},$_->{name},$_->{party},\"$_->{constituency}\",$signed,$modcom,$minister\n";
}

my $check = scalar keys %{$edm->{signatures}};
print STDERR "$count_check matched, but $check left in array\n" if $check;
print STDERR join(', ', keys %{$edm->{signatures}}) . "\n" if $check;

# Eventually spawn off into EDM package

sub get_edm_by_num {
    my $num = shift;
    my $mech = WWW::Mechanize->new;
    $mech->agent_alias('Windows Mozilla');

    $mech->get('http://edmi.parliament.uk/EDMi/Default.aspx');
    $mech->submit_form(
        form_name => 'Form1',
        button => '_MenuCtrl:_GoTo',
        fields => {
            '_MenuCtrl:ddlSession' => 891,
        },
    );
    $mech->get('http://edmi.parliament.uk/EDMi/Search.aspx');
    $mech->submit_form(
        form_name => 'Form1',
        button => 'btnGoTo',
        fields => {
            tbEDMNo => $num
        },
    );
    $mech->follow_link( url_regex => qr/EDMDetails/ );

    my $page = $mech->content();
    my $tree = HTML::TreeBuilder->new_from_content($page);
    my $box = $tree->look_down('class', 'Container');
    my $title = $box->look_down('class', 'TitleDetail')->as_trimmed_text;
    my $date = $box->look_down('class', 'DateDetail')->as_trimmed_text;
    my $text = $box->look_down('_tag', 'p')->as_trimmed_text;
    my $out = {
        title => $title,
    date => $date,
    text => $text,
    };

    my @sigs = $tree->look_down('id', 'SigsMode')->look_down('_tag', 'td');
    foreach (@sigs) {
        my $sponsor = 1;
        $sponsor = 3 if $_->look_down('_tag', 'b');
        $sponsor = 2 if $_->look_down('_tag', 'i');
        my $name = $_->as_trimmed_text;
        my ($last, $first) = $name =~ /^(.*?), (.*)$/;
        $name = "$first $last";
        # XXX
        $name =~ s/Kumar //;
        $name =~ s/Mike Weir/Michael Weir/;
        $name =~ s/Lembit Opik/Lembit \xd6pik/;
        $name =~ s/Jeffrey Donaldson/Jeffrey M Donaldson/; 
        $out->{signatures}{$name} = $sponsor;
    }

    return $out;
}

