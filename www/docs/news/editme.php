<?php 

/* TheyWorkForYou.com site news */

$all_news = array(

/* Draft I think that Tomski left in MT, probably delete it now?
37 => array("The Times has a grumble", <<<EOT
The venerable <i>Times</i> is grumpy. In a <a href="http://www.timesonline.co.uk/article/0,,17129-2060278,00.html">feature article</a> by the tabloid's <a href="http://www.explore.parliament.uk/Parliament.aspx?id=10305&glossary=true">lobby correspondent</a> Greg Hurst, the paper claims that some new MPs are asking frivolous written questions with the sole intention of improving their statistics on TheyWorkForYou.com. 

Here at TheyWorkForYou.com we are honoured to be featured so prominently in such a venerable organ as <i>The Times</i>.  

And we share a concern that overly crude measurement can distort behaviours. But the volunteers who run this site favour transparent hard data over lobby gossip, anecdote and anonymous parliamentary sources. So we examined the volume of Written Questios 

The Times also puiblished a typically despairing leader on the same subject, bemoaning the demise of parliamentary oratory and describing TheyWorkForYou as 'intimidatingly named'. 

and a leader, the tabloid MP's 'intimidatingly named' TheyWorkForYou.com  
EOT
, "2006-04-09 10:01:00"),
*/

39 => array('New features over the last couple of months', <<<EOT
Just a brief round-up of what we've added since our last piece of news.

We've added MPs' membership of select committees, taken from
<a href="http://www.parliament.uk/directories/hciolists/selmem.cfm">this
official list</a>, including membership history where we can work it out.

Our rankings for various numbers have been removed, to be replaced by
woolly text &ndash; we have <a href="http://www.theyworkforyou.com/help/#numbers">more
information on this</a>. To make up for it, we've done a bit more text
analysis, and added which departments and subjects an MP asks most
written questions about, so you can see that
<a href="http://www.theyworkforyou.com/mp/alan_williams/swansea_west">Alan Williams</a>
has asked more questions about Royal Residences than anything else, or that
<a href="http://www.theyworkforyou.com/mp/anne_main/st_albans">Anne Main</a> has asked
more questions on Flag Flying.

Firefox users can download a
<a href="http://mycroft.mozdev.org/download.html?name=theyworkforyou">search plugin</a>,
to enable easy searching of the site from Firefox's search bar.

And a few bugfixes - our handling of when Oral Answers finish should be a bit better than previously, 
and when searching a particular person's speeches, we no longer group the results by debate, which
was confusing, as you obviously expected to see all the speeches for that person that matched your
search terms.
EOT
, "2006-08-18 09:46:36"),

38 => array("Got an idea for a useful website?", <<<EOT
mySociety is running a call for proposals until 16th June. If you can
come up with an idea for a useful site like this one, and it beats all
the other ideas, we'll build it for free. <a href="http://www.mysociety.org/proposals2006/submit">Submit an idea</a>
or <a href="http://www.mysociety.org/proposals2006/view">read and comment on what other people have submitted</a>.
EOT
, "2006-06-06 06:06:06"),

37 => array("We've added the Lords, and more", <<<EOT
For your use and enjoyment we've added Hansard for the House of Lords (their debates,
written questions and ministerial statements, just like the Commons), and a page
on each of the <a href="/peers/">members of the House of Lords</a>. That isn't all - we've
also added a feature to the search to help you identify which MPs or
Lords are interested in a certain word or phrases. Just search as
usual and then click 'Show use by person'.
EOT
, "2006-06-01 12:34:56"),

36 => array("Changes to the Register of Members' Interests", <<<EOT
One of the many things our site does, probably without you realising, is to track the Register of Members' Interests, in which MPs are "to provide information of any pecuniary interest or other material benefit which a Member receives which might reasonably be thought by others to influence his or her actions, speeches or votes in Parliament, or actions taken in the capacity of a Member of Parliament". The latest entry published on the official site has always been shown on MP pages (<a href="http://www.theyworkforyou.com/mp/tony_blair/sedgefield#register">here's Tony Blair's</a>), however we kept all previous editions of the Register safe and sound. And now, after a bit of coding, you can view a history of the Register, either <a href="http://www.theyworkforyou.com/regmem/?f=2005-12-14">comparing particular editions</a>, or for particular MPs (<a href="http://www.theyworkforyou.com/regmem/?p=10001">Diane Abbott</a>, for example). Entries only have to stay on the Register for a year, so this can make for some interesting reading.

Happy New Year! :) <a href="http://www.theyworkforyou.com/regmem/">Changes to the Register of Members' Interests</a> 
EOT
, "2006-01-01 12:36:00"),

35 => array("TheyWorkForYou.com Wins Award", <<<EOT
We're proud to announce that TheyWorkForYou.com has won the  'Contribution to Civic Society' award at the <a href="http://www.newstatesman.co.uk/nma/nma2005/nma2005index.php">New Statesman magazine's New Media Awards</a>.

Thanks to the kind souls who nominated us. We enjoyed ourselves.

 
EOT
, "2005-07-05 23:54:13"),

34 => array("NEW! Email this page to a friend", <<<EOT
At the top right hand side of every (ex)MP's page, we've added a simple new feature: email the page to a friend.

<a href="/mp/">Have a go</a>, and let all your friends know how easy it is to keep tabs on what your former MP did and said in your name during the last Parliament. 
EOT
, "2005-04-14 23:43:50"),

33 => array("Play with Pledgebank, Pound the Streets", <<<EOT
Our good friends over at <a href="http://www.mysociety.org/">MySociety.org</a> are close to launching <a href="http://www.pledgebank.com/">Pledgebank</a>, a new web and sms service which  does what it says on the url.

To help test the concept,  they've set up a <a href="http://www.pledgebank.com/theywork">special TheyWorkForYou.com pledge</a> which reads:
<blockquote>
<i>"I will deliver a printed copy of my local MP's page on TheyWorkForYou.com to every house in my street but only if 100 other fans of TheyWorkForYou.com will too."
</i>
</blockquote>

If you're up for it, why not <a href="http://www.pledgebank.com/theywork">sign up now?</a>  
EOT
, "2005-04-13 22:41:23"),

32 => array("NEW! How MPs voted on Key Issues", <<<EOT
We've added a smashing new feature to all our <a href="/mps/">MP pages</a>, courtesy of Francis at <a href="http://www.publicwhip.org.uk">PublicWhip</a>

You can now check out which way MPs tended vote on half a dozen key issues from the last Parliament (Iraq, Fox Hunting, ID cards. Top Up Fees etc) via a nice, easy to understand panel. 

For example, mining the <a href="http://www.theyworkforyou.com/mp/graham_allen/nottingham_north#votingrecord">voting record of Graham Allen</a>, Labour MP for Nottingham North, shows that he was pretty dubious about the Iraq war, but very strongly in favour of a ban on fox hunting.

This information is the result sophisticated mining of PublicWhip's detailed vote data covering hundreds of Parliamentary divisions.

We hope it'll make it easier for you to keep track of where your MP really stands on key issues - after all, They Work For You. 
EOT
, "2005-04-05 22:59:57"),

31 => array("Channel 4 linking to TheyWorkForYou.com", <<<EOT
We're happy to announce that the Channel 4 website is directing people to TheyWorkForYou.com's MP pages via a <a href="http://www.channel4.com/news/microsites/E/election2005/yourmp.html">postcode box</a> on their <a href="http://www.channel4.com/news/microsites/E/election2005/">Election 2005 website</a>.

We've done a special paint job for users coming from the Channel 4 site, just to make them feel welcome. You can see an example <a href="http://www.theyworkforyou.com/mp/c4/paul_keetch/hereford">here</a>.

We'd love to know what you think of this paint job, as we're planning a redesign. As ever, email us at beta@theyworkforyou.com 
EOT
, "2005-04-05 22:53:32"),

30 => array("NEW! Email Alerts & Other Features", <<<EOT
 Since launching in June, we've made hundreds of small
     improvements to the site. However, the cold winter nights
     have sparked an explosion of activity, and we've launched a
     slew of new features in response to requests such as:

     <i>"Can you Email me when my MP next speaks, or when an issue
       I care about is raised?"</i>

     You all *so* wanted this. Near the top of every MP's page,
     and on every search results page, you'll see a link starting
     with 'Email me when...' Just click and go, or sign up by
     hand using <a href="http://theyworkforyou.com/alert/">http://theyworkforyou.com/alert/</a>

    <i> "Can I see when an issue was last raised in Parliament?"</i>

     Yes. At the top of every search results page you'll see a
     link that sorts the most recent result at the top. Ideal for
     keeping tabs on topical concerns such as
     <a href="http://theyworkforyou.com/search/?o=d&s=%22identity+card%22">http://theyworkforyou.com/search/?o=d&s=%22identity+card%22</a>

     <i>"Can I just search the stuff my MP has said?"</i>

     Yup. Go to an MP's page. See the red search box to the right
     of your MP's delicious photo? That's your baby. It'll search
     just that MP's contributions.

     <i>"What about Westminster Hall, Written Ministerial
       Statements & House of Commons' Committees?"</i>

     We're two-thirds done. See <a href="http://theyworkforyou.com/whall/">http://theyworkforyou.com/whall/</a> and
     <a href="http://theyworkforyou.com/wms/">http://theyworkforyou.com/wms/</a> We're busy tacking Committee
     proceedings, but these be hard.

     <i>"Is what you're doing legal?"</i>

     Yes. We are legit. Indeed, within a couple of months of launch
     we had Parliament's blessing, plenty of enthusiasm and a nice
     shiny licence to re-use Hansard. Given Parliament's history &
     traditions, such a swift & positive reaction is most admirable.

     FINALLY, TELL YOUR FRIENDS

     TheyWorkForYou.com is the best place to get the unadulterated
     lowdown on what your MP has said and done in your name.

     As the election approaches, we think the site could make a
     real difference to democratic transparency and engagement.

     If you agree, please tell your friends. Blog about us. Write
     about us. Link to us. Use our RSS feeds in your sites. Tell
     your enemies. Hell, even tell your parents. You're the only
     marketing we can afford!

     Best wishes,

    - Tom, on behalf of the TheyWorkForYou.com volunteers

     <a href="http://theyworkforyou.com/about/">http://theyworkforyou.com/about/</a>  - New volunteers welcome! 
EOT
, "2005-02-24 22:02:44"),

26 => array("New Release of TheyWorkForYou.com Source Code", <<<EOT
We've released version 8 of the <a href="https://sourceforge.net/projects/publicwhip/">TheyWorkForYou.com source code</a> which is available for download under an Open Source licence.

The update contains new code for:

- Westminster Hall Debates
- Written ministerial statements
- Better search code

Feel free to download it and have a play.

Whilst you're at it, don't forget we also publish a full <a href="http://www.theyworkforyou.com/raw/">XML version of the Hansard data</a>. 
EOT
, "2005-01-25 00:15:19"),

25 => array("Search by Date", <<<EOT
Thanks to the sterling efforts of a fine new volunteer (thanks David!), you can now sort any search results either by listing the <a href="http://www.theyworkforyou.com/search/?s=Uzbekistan&o=r">most relevant results first</a>, or <a href="http://www.theyworkforyou.com/search/?s=Uzbekistan&o=d">most recent results first</a>.


This is good news for those wanting to keep up to date with issues as they pop up in the Commons.

Next step on search is probably an RSS version of keyword searches. We're still very keen to improve overall relevancy of results - anyone out there fancy lending us a Google Appliance to play with? 

As ever, contact us on beta@theyworkforyou.com if you've got any suggestions how we might improve the site, or if you fancy volunteering. 
EOT
, "2005-01-25 00:16:09"),

20 => array("New! Ministerial Statements now included", <<<EOT
The <a href="http://www.theyworkforyou.com/">TheyWorkForYou.com</a> volunteers are busily crunching their way through the more esoteric corners of Hansard. By way of example, we've just incorporated <a href="http://www.theyworkforyou.com/wms/">Written Ministerial Statements</a> into the site.

Nope, most of us had never heard of them either, but apparently:

<i><a href="http://www.theyworkforyou.com/wms/">Written Ministerial Statements</a> were introducted in late 2002 to stop the practice of having "planted" or "inspired" questions designed to elicit Government statements.

They are just that - statements on a particular topic by a Government Minister.
</i>

Well, there you go. You live and learn. 

Next up: Select Committees, which promise to be an order of magnitude more challenging. We'll keep you posted.

PS We're always on the lookout for new volunteers - email us at beta@theyworkforyou.com if you are keen to help in almost any capacity. 
EOT
, "2005-01-17 17:44:47"),

19 => array("NEW! Westminster Hall debates now available.", <<<EOT
The volunteers have been busy over the past few days, and we are now one stage closer towards <a href="http://www.theyworkforyou.com/">TheyWorkForYou.com</a> becoming a truly comprehensive record of Parliamentary activity.

We've included <a href="http://www.theyworkforyou.com/whall/">debates from Westminster Hall</a>, which is a new-ish forum sitting in parallel to the main Commons Chamber. Learn more about it <a href="http://www.explore.parliament.uk/Parliament.aspx?id=10416&glossary=true">here</a>.

<a href="http://www.theyworkforyou.com/whall/">Westminster Hall Debates</a> were introduced in 1999 with the aim of encouraging constructive rather than confrontational debate between MPs. 

As ever, we'll let you be the judge. 

Next up, parsing all the various House of Commons' Committees... .




 
EOT
, "2004-12-22 23:47:19"),

18 => array("RSS feed of your MP's recent appearances", <<<EOT
Don't forget you can keep track of any MP's recent House of Commons appearances via an RSS newsfeed. You'll find the link in the right-hand column of each individual MP's page.

For example, the RSS feed of <a href="http://www.theyworkforyou.com/mp/?pid=10508">Barabara Roche's</a> recent appearances in the House of Commons can be found at the following web address:

<blockquote><a href="http://www.theyworkforyou.com/rss/mp/10508.rdf">http://www.theyworkforyou.com/rss/mp/10508.rdf</a>
</blockquote>

RSS is a simple way to publish & distribute content which is frequently updated  (<a href="http://news.bbc.co.uk/1/hi/help/3223484.stm">learn more here</a>.)

Some enlightened MPs are now incorporating this useful feed of their Parliamentary activity into their websites. See <a href="http://www.richardallan.org.uk/">Richard Allen's</a> website for a good example. 
EOT
, "2004-11-27 16:38:41"),

16 => array("NEW! MPs' Expenses", <<<EOT
At the bottom of <a href="http://www.theyworkforyou.com/mps/">each MP's page</a>, you can now see how much money your MP has claimed in allowances over the past three years.

For example, it is good to note that <a href="http://www.theyworkforyou.com/mp/?pid=10508#expenses">Barbara Roche</a>, a North London MP, does not claim excessive travel expenses.

Bear in mind that proper democracy does cost money, so please think twice before using these data as a stick with which to beat your MP. An "expensive" MP might be providing excellent value for money. And vice versa. 
EOT
, "2004-11-08 10:11:14"),

11 => array("TheyWorkForYou.com Development Wiki now public", <<<EOT
Shortly after we launched our beta test in June we decided we should also open up access to the wiki we use to help develop TheyWorkForYou.com so that anyone can also have a go, muck in, or just laugh at us.

Of course, that took far longer than we planned, but having <a href="http://www.theyworkforyou.com/news/archives/2004/07/18/new_full_source_.php">published the source code</a>, we should open up the wiki too.

We don't like wiki spam, so it will remain passworded, but anyone who wants a browse can tuck in at the <a href="http://www.theyworkforyou.com/wiki/moin.cgi">TheyWorkForYou.com wiki</a>

username: theyworkforyou
password: n0vemb3r (n-zero-vemb-three-r)

(dissemination is fine, but please don't post the link with the login details embedded in the url)

In case you're wondering what we're up to, well, we're just rousing ourselves for another tilt at completing our vision before the next election is called, so if anyone python or php skills and fancies lending a hand, do email us at beta@theyworkforyou.com 
EOT
, "2004-10-01 23:14:13"),

8 => array("New! Full Source Code Published", <<<EOT
We're <i>really</i> keen for others to use both our code and the data feeds we make available to make the UK's Parliament more accessible.

Most notably, we've recently <a href="https://sourceforge.net/project/showfiles.php?group_id=87640">published the source code</a> for the front and back end of TheyWorkForYou.com. 

It's available here: <a href="https://sourceforge.net/project/showfiles.php?group_id=87640">https://sourceforge.net/project/showfiles.php?group_id=87640</a> It's mainly php at the front, mysql at the back while python does the parsing, all stuck together with the usual glue code. We use <a href="http://www.xapian.org/">Xapian</a> for the search engine.

<b>But there's more!</b> Below, you'll find links to various other open data feeds and resources we've produced to date:

<a href="http://www.theyworkforyou.com/raw/">http://www.theyworkforyou.com/raw/</a>
    - XML files of debates / written answers back to June 2001 (House of Commons only - not Westminster Hall)

<a href="http://www.publicwhip.org.uk/project/data.php">http://www.publicwhip.org.uk/project/data.php</a>
    - MP id and constituency index files
    - MP performance data
    - Voting record matrices

<a href="http://theyworkforyou.com/rss/mp/10508.rdf">http://theyworkforyou.com/rss/mp/10508.rdf</a>
    - Typical MP 'recent appearances' RSS feed, uses Person id from <a href="http://www.publicwhip.org.uk/data/people.xml">here</a>

<a href="http://www.publicwhip.org.uk/project/code.php">http://www.publicwhip.org.uk/project/code.php</a>
    - How to use the parser source code

<a href="http://sourceforge.net/projects/publicwhip/">http://sourceforge.net/projects/publicwhip/</a>
    - Sourceforge account, for anonymous CVS access

<a href="https://lists.sourceforge.net/lists/listinfo/publicwhip-playing ">https://lists.sourceforge.net/lists/listinfo/publicwhip-playing </a>
    - Join an email list to talk about all of the above. 
EOT
, "2004-07-18 22:58:23"),

7 => array("Public Beta Now Live", <<<EOT
Welcome to the public beta of TheyWorkForYou.com, which launched on Sunday 6th June 2004 at the <a href="http://www.notcon04.com/" title="link to NotCon04 conference website">NotCon04</a> conference.

We hope you enjoy using the service during its public beta phase, which will last for  a while as we unpick all the bugs and tweak the features.  

We want to know everything: what you like, what you hate, what works, what's broken, what could we do better. The lot. Don't hold back.

The search engine is our main area of immediate focus - we know we've got months of tuning and tweaking of search results to come. We know enough to know that great search is hard, and that your feedback is crucial.

In the meantime, please enjoy being the first people to scribble in the margins of Hansard. May you be first of many.

Send all bug reports (and feature suggestions) to <a href="mailto:beta@theyworkforyou.com">beta@theyworkforyou.com</a>

Finally, a big 'thank you' to everyone who helped test the site during the private beta phase over the past two weeks. Your feedback has been invaluable. 

More than the usuals,

- <i><a href="http://www.theyworkforyou.com/about/" title="link to About Us page">The TheyWorkForYou.com Volunteers</a></i> 
EOT
, "2004-06-06 03:02:53"),

6 => array("Want to help make us complete?", <<<EOT
Please remember that this isn't yet a complete record of our MPs' activities in the House of Commons. For that we need to add the transcripts of Select Committee proceedings and a load of other fiddly and esoteric information.  

We also want to add data from before 2001. And there's always the Lords... We yearn to be a complete record.

But to do this, we will need to ensure our key developers are kept fed. If you're in a sugar daddy frame of mind, we'd be only too pleased to accept donations. 

Just email us on <a href="mailto:beta-feedback@theyworkforyou.com">beta-feedback@theyworkforyou.com</a>
 
EOT
, "2004-05-21 22:15:22"),

5 => array("Know someone who'd like this website?", <<<EOT
If you know someone who would appreciate being a beta tester, please email us their details to <a href="mailto:beta-admin@theyworkforyou.com">beta-admin@theyworkforyou.com</a>

Many thanks. 
EOT
, "2004-05-21 22:55:24"),

4 => array("Welcome to our private beta test", <<<EOT
Hello all, and thank you for helping to road test TheyWorkForYou.com. We hope you enjoy our new baby; we're proud of her, even though she's still somewhat rough around the edges.

We've just entered our 'closed beta' phase - you'll need a password to access the site until we move into our open beta phase sometime in June.

Please email your feedback on <a href="mailto:beta@theyworkforyou.com">beta@theyworkforyou.com</a>, or you can just add your comments to this blog. 

We want to know everything: what you like, what you hate, what works, what's broken, what could we do better. The lot. Don't hold back.

The search engine is our main area of immediate focus - we know we've got months of tuning and tweaking of search results to come. We know enough to know that great search is hard, and that your feedback is crucial.

In the meantime, please enjoy being the first people to scribble in the margins of Hansard. May you be first of many.

- <em>The TheyWorkForYou.com volunteers</em> 
EOT
, "2004-05-21 22:55:24")
);

// General news functions
function news_format_body($content) {
	return "<p>" . str_replace("\n\n", "<p>", $content);
}
function news_format_ref($title) {
	$x = preg_replace("/[^a-z0-9 ]/", "", strtolower($title));
	$x = substr(str_replace(" ", "_", $x), 0, 16);
	return $x;
}
function news_individual_link($date, $title) {
	return "/news/archives/" . str_replace("-", "/", substr($date, 0, 10)) . "/" . news_format_ref($title) . ".php";
}


