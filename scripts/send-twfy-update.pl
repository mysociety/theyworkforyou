#!/usr/bin/perl -w
#
# Script to send email update to opted-in users
# 
# $Id: send-twfy-update.pl,v 1.2 2009-05-07 09:31:48 louise Exp $

use strict;

use DBI;
use FindBin;
use lib '../../perllib';
use mySociety::Config;
use mySociety::Email;
use mySociety::EmailUtil;

mySociety::Config::set_file("$FindBin::Bin/../conf/general");

my $dsn = "DBI:mysql:" . mySociety::Config::get('DB_NAME') . ':' . mySociety::Config::get('DB_HOST');

my $dbh = DBI->connect ($dsn, mySociety::Config::get('DB_USER'), mySociety::Config::get('DB_PASS'), { RaiseError => 1});

my $sth = $dbh->prepare ("SELECT firstname,lastname,email from users where optin='1' and deleted='0' and confirmed='1'");
$sth->execute();

while (my @ary = $sth->fetchrow_array()) {
	my $firstname = $ary[0];
	my $lastname = $ary[1];
	my $email = $ary[2];
	my $name = $ary[3];
	print "$firstname:$lastname:$email\n";
	my $mailbody = "";
	my $subject = "TheyWorkForYou.com election mailing";
	$mailbody .= "

Hi $firstname $lastname,

You're getting this message because you once registered
for occasional email updates from TheyWorkForYou.com.

This particular mail is sent on that most occasional
of occasions: a United Kingdom general election.

First, our obligatory plea for help
===================================

We don't want cash, and we don't need love, but we do
need your help.

TheyWorkForYou.com has no marketing budget, no posters,
and no ad agencies working for us. We need *you* to be 
our marketing department. Before you read on, (and only
if it's before May 6th 2005), please forward this email
to some other British electors you might know. Or blog 
about us, if you're that way inclined.

...done it?

Ta, thanks. 

(There're more ways in which you can help below)

Your MP's Report Card from TheyWorkForYou
=========================================

Still pondering whether to vote for or against your 
incumbent MP? Well, to help you, we've sluiced together
all the facts we could about their behaviour and voting
patterns over the past few years, and squeezed them onto 
one web page. Whether they were pro or against Iraq, 
rebellious or absentee, aloof or taking money from the 
dodgiest people, frugal with the public purse, or
blowing it all on train tickets, it's all here.

We think you'll like it, and even if you thought you 
knew your MP, you may be in for a few surprises. 

Just type in your postcode:

    http://www.theyworkforyou.com/mp/

== Not voting this year? Tell the world why at 
http://www.notapathetic.com/ ==


Electoral Fraud Special
=======================

If you're as worried as we are about electoral fraud, 
here's what you can do: 

* Don't vote by post. Turn up: it's worth it. 

* Ring your council, ask for the Electoral Register 
Division, and check you're name is not on the 
\"marked register\" - ie, someone has already voted in 
your name. Find your council's number here:
http://www.upmystreet.com/lgc_roles/ 

* Finally, if you suspect fraud, email 

 electoral.fraud\@guardian.co.uk, 

who are collecting incidents to report on, and contact 
the police.

Heeeelp us! (No, we don't need money)
======================================

 Our biggest constraint is publicity.

We're rather allergic to spending time and money on 
blowing our own trumpet
(Just writing this is making us feel a bit shifty).

But without publicity, people who might want to learn 
more about their MP and the issues this election won't 
know about us. And that means they won't 
find out everything they need about this election.

The other constraint is this damned Internet thing. 
Try as we might, we can't get away from it - even though
we know that many people don't have access to it. Or 
when they do, they're so battered with pop-ups and 
viruses, they'd never find us.

So we wondered if you might be able to help us a little
with publicity, and escaping our fancy techno-shackles.

Here's how:

Pledge, Print and Post
======================

All of us here have pledged to load up our own MP's 
report card from http://www.theyworkforyou.com/mp, 
print it out ten times on our bubblejet printers, and 
deliver a copy to ten other houses in our neighbourhood.
As we're mild cowards, we'll only do this if 100 other
people across Britain agree to pledge, print and post 
with us.

If you think it's important for your fellow voters to 
be impartially informed, rather than drowned in spin 
and ad copy, we'd like you to join us.

It'll only take a few minutes, and, you know, it gets 
you out of the house.

Click here:

http://www.pledgebank.com/theywork to sign up with us.

You'll only have to do it if 100 other people are as 
brave as you.

Other Things To Do
==================

If that's a bit *too* scary, just forward this email 
(or your MPs Report Card) around your friends; 
or if you're one of those blogger people, write about 
us.


Other Sites You Might Like
==========================

We're just one of a growing community of independent
British civic sites which aim to provide tools for all
kinds of civic and political action.

A list of some web sites that we know about is below.
Some of the sites we know the people involved, others
we have no connection with. But we all think they're
brilliant, and we really hope they'll help you make an
informed choice, and act on it, this election.

http://www.publicwhip.org.uk/election.php
- the one minute quiz to find out how you should vote

http://www.whoshouldyouvotefor.com/
- more detailed analysis of you and your politics

http://www.politicalsurvey2005.com/
- now find out how you compare to the rest of Britain

http://www.notapathetic.com
- say why you're not voting (if that's what you've
decided to do), and make yourself heard.

http://www.writetothem.com/
- our sister site for contacting your representatives,
local, national and European.

Finally, Our Partly Political Broadcast
=======================================

You'll hear a lot from the politicians and pundits in 
the next few weeks about how this is the 
\"Internet Election\".

We think it may will be; but that won't be thanks to 
them. We think it'll be due to people like you, and the
volunteers who build these sites, to make this an 
election based on free information and no more spin.

For the last parliament, for good or bad, they worked 
for us.

But sometimes - and especially at election time - you 
have to do the work yourself.

Have a good election,

 -- The TheyWorkForYou Volunteers.


Unsubscribe?
============
If you never want to hear from us again then it's fine, we
won't cry - you can shut us up by logging in and setting the
\"occasional update emails\" setting to \"no\" at:
http://www.theyworkforyou.com/user/login/?ret=/user/?pg=edit
But then you're *so* off our Christmas card list.


";

    my $message = mySociety::Email::construct_email({
        _body_ => $mailbody,
	To => [ [ $email, "$firstname $lastname" ] ],
	From => [ 'team@theyworkforyou.com', 'TheyWorkForYou' ],
	Subject => $subject
    });
    my $result = mySociety::EmailUtil::send_email($message, 'team@theyworkforyou.com', $email);
    if ($result != mySociety::EmailUtil::EMAIL_SUCCESS) {
        print "Failed to send email to $email\n";
    }
}

$sth->finish();
$dbh->disconnect();

