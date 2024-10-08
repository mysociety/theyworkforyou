<?php

/* TheyWorkForYou.com site news */

$all_news = [

    49 => ['Blimey. It looks like the Internets won', <<<EOT
        After a tremendous response from you, with over 7,000 members on our <a href="http://www.facebook.com/group.php?gid=50061011231">Facebook
        group</a>, 4,000 messages sent to MPs, and a helpful <a href="http://www.publications.parliament.uk/pa/ld200809/ldselect/ldmerit/16/1603.htm">4th
        Report of the House of Lords Merits of Statutory Instruments Committee</a>, it
        appears that the vote has been cancelled -
        <a href="http://www.guardian.co.uk/politics/2009/jan/21/mps-expenses">Guardian</a>,
        <a href="http://www.timesonline.co.uk/tol/news/politics/article5559704.ece">Times</a>,
        <a href="http://news.bbc.co.uk/1/hi/uk_politics/7842402.stm">BBC</a>.

        As President Obama said in his inauguration speech: "And those of us who manage
        the public's dollars will be held to account - to spend wisely, reform bad
        habits, and do our business in the light of day - because only then can we
        restore the vital trust between a people and their government."

        Read <a href="http://www.mysociety.org/2009/01/21/blimey-it-looks-like-the-internets-won/">our victory mySociety blog post</a>,
        and do join our mailing list. :-)
        EOT, '2009-01-21 15:30:00', 'Matthew'],

    48 => ['API keys, and improved internal links', <<<EOT
        In order to monitor the service for abuse, help with support and maintenance,
        locate large volume/ commercial users to ask them to contribute to our costs,
        and provide users with usage statistics of their application, we're adding API
        keys to our <a href="https://www.theyworkforyou.com/api/">API</a>. Apologies to
        current API users who will be inconvenienced; the API will allow key-less calls
        for a short time, during which current users can get a key and update their
        code.

        In front end news, we now locate whenever an old version of Hansard is
        referenced (which they do by date and column number, e.g. <a
        href="https://www.theyworkforyou.com/search/?pop=1&s=date:20080229+column:1425"><i>Official
        Report</i>, 29 February 2008, column 1425</a>) and turn the citation into a
        link to a search for the speeches in that column on that date. This only really
        became feasible when we moved server, upgraded <a
        href="http://www.xapian.org/">Xapian</a>, and added date and column number
        metadata (among others), allowing much more advanced and focussed searching -
        the <a href="https://www.theyworkforyou.com/search/">advanced search form</a>
        gives some other ideas. Perhaps in future we'll be able to add some
        crowd-sourcing game to match the reference to the exact speech, much like our
        <a href="https://www.theyworkforyou.com/video/">video matching</a> (nearly 80%
        of our archive done!). :)
        EOT, '2008-07-18 14:01:00', 'Matthew'],

    47 => ['Video on TheyWorkForYou', <<<EOT
        <p>We&#8217;re very excited to announce that TheyWorkForYou now includes <a href="/video/">video</a> of <a href="/debates/">debates in the House of Commons</a> - but <a href="/video/">we need your help</a> to match up each speech with the video footage.</p>

        <p>It&#8217;s really easy to help out.  We&#8217;ve built a <a href="https://www.theyworkforyou.com/video/">really simple, rather addictive system</a> that lets anyone with a few spare minutes match up a randomly-selected speech from Hansard against the correct snippet of video.  You just listen out for a certain speech, and when you hear it you hit the big red &#8216;Now&#8217; button. Your clip will then immediately go live on TheyWorkForYou next to the relevent speech, improving the site for everyone. Yay!</p>

        <p>You can start matching up speeches with video snippets right away, but if you take 30 seconds to <a href="https://www.theyworkforyou.com/user/?pg=join&#038;ret=/video/">register a username</a> then we&#8217;ll log every speech that you match up and recognise your contribution on our <a href="https://www.theyworkforyou.com/video/">&#8220;top timestampers&#8221; league table</a>.  We&#8217;ll send out mySociety hoodies to the top timestampers - they&#8217;re reserved exclusively for our volunteers as a badge of honour.</p>

        <a href="http://www.mysociety.org/2008/06/01/video-recordings-of-the-house-of-commons-on-theyworkforyoucom/">Etienne's post on the mySociety blog</a>
        has some background information on the project.
        EOT, '2008-06-02 18:56:00', 'Matthew'],

    46 => ['The Scottish Parliament', <<<EOT
        Thanks mainly to our Edinburgh-based volunteer <a
        href="http://longair.net/mark/">Mark Longair</a>, TheyWorkForYou now covers
        Scottish Parliament <a href="/sp/">debates</a> and <a href="/spwrans/">written
        answers</a>, right back to the start of this Parliament in 1999. That means
        that <a href="/msps/">each MSP</a>, just like MPs, Lords, and MLAs, now has
        their own page, linking to speeches and questions they have made. For example,
        here are the current party leaders:
        <a href="/msp/alex_salmond">Alex Salmond</a>,
        <a href="/msp/wendy_alexander">Wendy Alexander</a>,
        <a href="/msp/annabel_goldie">Annabel Goldie</a>,
        <a href="/msp/nicol_stephen">Nicol Stephen</a>, and
        <a href="/msp/robin_harper">Robin Harper</a>.

        As with other types of representative, you can subscribe to email alerts or an
        RSS feeds for their latest appearances; or subscribe to an email alert when a
        particular word or phrase is mentioned, either anywhere or just in the Scottish
        Parliament.

        To go with this launch of a large amount of new data, we've rejigged the
        site navigation slightly to make it a bit easier to follow. Each body now has
        its own main tab, with links to the sections and representatives under each one
        (so lists of MPs and Lords are now under &ldquo;UK&rdquo;).

        Along with improvements to our <a href="/search/">search engine</a>, including
        date range search, spelling correction, departmental search, and more, we're
        launching this now to mark the success in getting endorsements for our <a
        href="/freeourbills/">Free Our Bills campaign</a>, and we'll be rolling out
        more goodies as the campaign goes along.
        EOT, '2008-05-06 13:35:28', 'Matthew'],

    45 => ['Shadow ministers and spokespersons', <<<EOT
        TheyWorkForYou now includes shadow cabinet positions, shadow ministers and
        spokespersons for the Conservatives, Liberal Democrats, Plaid Cymru and SNP.
        This information is given both on a person's page and within any debates they
        speak in, which could help explain why your local MP is speaking on transport
        or foreign affairs a lot. :)
        EOT, '2008-04-30 08:25:00', 'Matthew'],

    44 => ['Please donate to help us expand TheyWorkForYou', <<<EOT
        We&#8217;ve been working on Public Bill (n&#233;e Standing) Committees, and some lovely new volunteers have been working their socks off on the Scottish Parliament, to be added to <a href="https://www.theyworkforyou.com">TheyWorkForYou</a>. Yay!

        Unfortunately, the server that TheyWorkForYou sits on is almost full, so we can&#8217;t launch their hard work. Boo!

        TheyWorkForYou isn&#8217;t an externally funded project, and we need funding from other sources to keep it growing and improving. So if the season has filled you with generosity of spirit, why not <a href="https://secure.mysociety.org/donate/">drop us a few pennies</a> to pay for some upgrades? Any extra beyond what we need will go into the general pot to keep <a href="http://www.mysociety.org/">mySociety</a> running and the developers from starving.
        EOT, '2007-12-21 10:07:35', 'Tom'],

    43 => ['The Queen in Parliament', <<<EOT
        Whilst we cover MPs and Lords, there's always been one other individual who speaks in
        Parliament whom we missed. That anomaly has now been fixed, and so you can now view
        <a href="https://www.theyworkforyou.com/royal/elizabeth_the_second">the Queen's
        page</a> on TheyWorkForYou. :)
        This means you can sign up for an email alert whenever she speaks
        in Parliament, subscribe to the
        <a href="https://www.theyworkforyou.com/rss/mp/13935.rdf">RSS feed</a> of the same,
        or just look at past
        <a href="https://www.theyworkforyou.com/search/?pid=13935&pop=1">prorogation and Queen's speeches</a>
        more easily than anywhere else I've found.
        EOT, '2007-09-11 15:34:47', 'Matthew'],

    42 => ['Missing confirmation emails', <<<EOT
        Whilst doing some routine maintenance, I spotted that due to a
        server configuration mistake, TheyWorkForYou has been
        failing to send out any confirmation emails since mid-August.
        Very many apologies to anyone affected - emails from the past
        few days have now been sent out, and I've got in touch
        with anyone who signed up in the days prior to
        that to let them know what happened.
        EOT, "2007-09-03 17:12:47", 'Matthew'],

    41 => ['The Northern Ireland Assembly', <<<EOT
        I'm extremely proud to announce that TheyWorkForYou now covers
        the debates of the Northern Ireland Assembly from its
        <a href="/ni/?d=1998-07-01">creation in 1998</a> to its current
        <a href="/ni/?d=2006-12-04">Transitional Assembly</a> status.
        Everything should integrate with the rest of the site, so
        MLAs who are also MPs only have the one central page, such as
        <a href="/mp/gerry_adams/belfast_west">Gerry Adams</a> or
        <a href="/mp/ian_paisley/north_antrim">Ian Paisley</a>.

        I've done this voluntarily, in secret &mdash;
        this announcement comes as much as a surprise to everyone else involved with
        TheyWorkForYou as it does to you &mdash;
        partly to try out
        <a title="The moment I realised I should simply make &lt;b&gt;, &lt;i&gt;, and &lt;font&gt; nestable to get everything to parse was quite good"
         href="http://www.crummy.com/software/BeautifulSoup/">Beautiful Soup</a>,
        partly because I obviously didn't have enough to do writing a
        <a href="http://petitions.pm.gov.uk/">No10 Petitions</a> website and
        answering mountains of support mail, partly as an early Christmas present for everyone I know in the world
        of the internets (hey guys), and partly to show how easy I think it is and that there's no excuse
        for people not to volunteer to do the Scottish Parliament
        and Welsh Assembly. :)
        <a href="http://project.knowledgeforge.net/ukparse/trac/browser/trunk/parlparse/pyscraper/ni">Here's all the code</a>
        (and there's not much; the scraper is 28 lines long, the name resolver 273, and the parser only 343 &mdash; including comments!)
        for scraping and parsing the Northern Ireland Assembly,
        <a href="http://project.knowledgeforge.net/ukparse/trac/browser/trunk/parlparse/members">the XML list of Assembly Members</a>
        I created was quite important, and then it was a matter of adapting the existing
        <a href="https://secure.mysociety.org/cvstrac/dir?d=mysociety/twfy">TheyWorkForYou code</a> to add
        a new major type (and completely rewrite the enter/leaving code as that assumed you couldn't be
        in more than one Parliament/Assembly at once...).

        I'm sure there are bugs around the place (<a href="/contact/">do let us know</a>), but I'm off on holiday for a week now;
        I look forward to my return (so don't expect a quick reply to suggestions or
        complaints)! :-)

        ATB,<br>
        <a href="http://www.dracos.co.uk/">Matthew Somerville</a>
        EOT, "2006-12-11 00:00:00", 'Matthew'],

    40 => ['TheyWorkForYou API', <<<EOT
        Today, we launched an API (Application Programming Interface) to the
        data TheyWorkForYou contains. This lets other sites query our site for
        various bits of information, such as the constituency or MP for a particular
        postcode, the debates for a particular date, the comments left on a certain
        MP's speeches, and so on. It's not complete
        and will hopefully have improvements in future &ndash;
        <a href="/api/">see the
        documentation and have a play with it</a>. Do dive in, and <a href="/contact/">let us
        know</a> of any interesting or fun applications you come up with! The
        immediate new feature on TheyWorkForYou itself is the list of
        neighbouring constituencies on an MP's page.

        Here's my <a href="http://www.mysociety.org/2006/barcamp-london/">BarCamp
        Presentation</a> launching the API, and a <a href="http://www.dracos.co.uk/work/theyworkforyou/api/fabfarts/">couple</a>
        of <a href="http://www.mysociety.org/2006/09/02/battle-your-way-to-sedgefield/">applications</a> using it.
        EOT, "2006-09-02 17:10:00"],

    39 => ['New features over the last couple of months', <<<EOT
        Just a brief round-up of what we've added since our last piece of news.

        We've added MPs' membership of select committees, taken from
        <a href="http://www.parliament.uk/directories/hciolists/selmem.cfm">this
        official list</a>, including membership history where we can work it out.

        Our rankings for various numbers have been removed, to be replaced by
        woolly text &ndash; we have <a href="https://www.theyworkforyou.com/help/#numbers">more
        information on this</a>. To make up for it, we've done a bit more text
        analysis, and added which departments and subjects an MP asks most
        written questions about, so you can see that
        <a href="https://www.theyworkforyou.com/mp/alan_williams/swansea_west">Alan Williams</a>
        has asked more questions about Royal Residences than anything else, or that
        <a href="https://www.theyworkforyou.com/mp/anne_main/st_albans">Anne Main</a> has asked
        more questions on Flag Flying.

        Firefox users can download a
        <a href="http://mycroft.mozdev.org/download.html?name=theyworkforyou">search plugin</a>,
        to enable easy searching of the site from Firefox's search bar.

        And a few bugfixes - our handling of when Oral Answers finish should be a bit better than previously,
        and when searching a particular person's speeches, we no longer group the results by debate, which
        was confusing, as you obviously expected to see all the speeches for that person that matched your
        search terms.
        EOT, "2006-08-18 09:46:36"],

    38 => ["Got an idea for a useful website?", <<<EOT
        mySociety is running a call for proposals until 16th June. If you can
        come up with an idea for a useful site like this one, and it beats all
        the other ideas, we'll build it for free. <a href="http://www.mysociety.org/proposals2006/submit">Submit an idea</a>
        or <a href="http://www.mysociety.org/proposals2006/view">read and comment on what other people have submitted</a>.
        EOT, "2006-06-06 06:06:06"],

    37 => ["We've added the Lords, and more", <<<EOT
        For your use and enjoyment we've added Hansard for the House of Lords (their debates,
        written questions and ministerial statements, just like the Commons), and a page
        on each of the <a href="/peers/">members of the House of Lords</a>. That isn't all - we've
        also added a feature to the search to help you identify which MPs or
        Lords are interested in a certain word or phrases. Just search as
        usual and then click 'Show use by person'.
        EOT, "2006-06-01 12:34:56"],

    36 => ["Changes to the Register of Members' Interests", <<<EOT
        One of the many things our site does, probably without you realising, is to track the Register of Members' Interests, in which MPs are "to provide information of any pecuniary interest or other material benefit which a Member receives which might reasonably be thought by others to influence his or her actions, speeches or votes in Parliament, or actions taken in the capacity of a Member of Parliament". The latest entry published on the official site has always been shown on MP pages (<a href="https://www.theyworkforyou.com/mp/tony_blair/sedgefield#register">here's Tony Blair's</a>), however we kept all previous editions of the Register safe and sound. And now, after a bit of coding, you can view a history of the Register, either <a href="https://www.theyworkforyou.com/regmem/?f=2005-12-14">comparing particular editions</a>, or for particular MPs (<a href="https://www.theyworkforyou.com/regmem/?p=10001">Diane Abbott</a>, for example). Entries only have to stay on the Register for a year, so this can make for some interesting reading.

        Happy New Year! :) <a href="https://www.theyworkforyou.com/regmem/">Changes to the Register of Members' Interests</a>
        EOT, "2006-01-01 12:36:00"],

    35 => ["TheyWorkForYou.com Wins Award", <<<EOT
        We're proud to announce that TheyWorkForYou.com has won the  'Contribution to Civic Society' award at the <a href="http://www.newstatesman.co.uk/nma/nma2005/nma2005index.php">New Statesman magazine's New Media Awards</a>.

        Thanks to the kind souls who nominated us. We enjoyed ourselves.


        EOT, "2005-07-05 23:54:13"],

    34 => ["NEW! Email this page to a friend", <<<EOT
        At the top right hand side of every (ex)MP's page, we've added a simple new feature: email the page to a friend.

        <a href="/mp/">Have a go</a>, and let all your friends know how easy it is to keep tabs on what your former MP did and said in your name during the last Parliament.
        EOT, "2005-04-14 23:43:50"],

    33 => ["Play with Pledgebank, Pound the Streets", <<<EOT
        Our good friends over at <a href="http://www.mysociety.org/">MySociety.org</a> are close to launching <a href="http://www.pledgebank.com/">Pledgebank</a>, a new web and sms service which  does what it says on the url.

        To help test the concept,  they've set up a <a href="http://www.pledgebank.com/theywork">special TheyWorkForYou.com pledge</a> which reads:
        <blockquote>
        <i>"I will deliver a printed copy of my local MP's page on TheyWorkForYou.com to every house in my street but only if 100 other fans of TheyWorkForYou.com will too."
        </i>
        </blockquote>

        If you're up for it, why not <a href="http://www.pledgebank.com/theywork">sign up now?</a>
        EOT, "2005-04-13 22:41:23"],

    32 => ["NEW! How MPs voted on Key Issues", <<<EOT
        We've added a smashing new feature to all our <a href="/mps/">MP pages</a>, courtesy of Francis at <a href="http://www.publicwhip.org.uk">PublicWhip</a>

        You can now check out which way MPs tended vote on half a dozen key issues from the last Parliament (Iraq, Fox Hunting, ID cards. Top Up Fees etc) via a nice, easy to understand panel.

        For example, mining the <a href="https://www.theyworkforyou.com/mp/graham_allen/nottingham_north#votingrecord">voting record of Graham Allen</a>, Labour MP for Nottingham North, shows that he was pretty dubious about the Iraq war, but very strongly in favour of a ban on fox hunting.

        This information is the result sophisticated mining of PublicWhip's detailed vote data covering hundreds of Parliamentary divisions.

        We hope it'll make it easier for you to keep track of where your MP really stands on key issues - after all, They Work For You.
        EOT, "2005-04-05 22:59:57"],

    31 => ["Channel 4 linking to TheyWorkForYou.com", <<<EOT
        We're happy to announce that the Channel 4 website is directing people to TheyWorkForYou.com's MP pages via a <a href="http://www.channel4.com/news/microsites/E/election2005/yourmp.html">postcode box</a> on their <a href="http://www.channel4.com/news/microsites/E/election2005/">Election 2005 website</a>.

        We've done a special paint job for users coming from the Channel 4 site, just to make them feel welcome. You can see an example <a href="https://www.theyworkforyou.com/mp/c4/paul_keetch/hereford">here</a>.

        We'd love to know what you think of this paint job, as we're planning a redesign. As ever, <a href="/contact/">get in touch</a>.
        EOT, "2005-04-05 22:53:32"],

    30 => ["NEW! Email Alerts & Other Features", <<<EOT
         Since launching in June, we've made hundreds of small
             improvements to the site. However, the cold winter nights
             have sparked an explosion of activity, and we've launched a
             slew of new features in response to requests such as:

             <i>"Can you Email me when my MP next speaks, or when an issue
               I care about is raised?"</i>

             You all *so* wanted this. Near the top of every MP's page,
             and on every search results page, you'll see a link starting
             with 'Email me when...' Just click and go, or sign up by
             hand using <a href="https://www.theyworkforyou.com/alert/">https://www.theyworkforyou.com/alert/</a>

            <i> "Can I see when an issue was last raised in Parliament?"</i>

             Yes. At the top of every search results page you'll see a
             link that sorts the most recent result at the top. Ideal for
             keeping tabs on topical concerns such as
             <a href="https://www.theyworkforyou.com/search/?o=d&s=%22identity+card%22">https://www.theyworkforyou.com/search/?o=d&s=%22identity+card%22</a>

             <i>"Can I just search the stuff my MP has said?"</i>

             Yup. Go to an MP's page. See the red search box to the right
             of your MP's delicious photo? That's your baby. It'll search
             just that MP's contributions.

             <i>"What about Westminster Hall, Written Ministerial
               Statements & House of Commons' Committees?"</i>

             We're two-thirds done. See <a href="https://www.theyworkforyou.com/whall/">https://www.theyworkforyou.com/whall/</a> and
             <a href="https://www.theyworkforyou.com/wms/">https://www.theyworkforyou.com/wms/</a> We're busy tacking Committee
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

             <a href="https://www.theyworkforyou.com/about/">https://www.theyworkforyou.com/about/</a>  - New volunteers welcome!
        EOT, "2005-02-24 22:02:44"],

    26 => ["New Release of TheyWorkForYou.com Source Code", <<<EOT
        <strong>Update:</strong> New URL for the code.

        We've released version 8 of the <a href="https://secure.mysociety.org/cvstrac/dir?d=mysociety/twfy">TheyWorkForYou.com source code</a> which is available for download under an Open Source licence.

        The update contains new code for:

        - Westminster Hall Debates
        - Written ministerial statements
        - Better search code

        Feel free to download it and have a play.

        Whilst you're at it, don't forget we also publish a full <a href="https://www.theyworkforyou.com/raw/">XML version of the Hansard data</a>.
        EOT, "2005-01-25 00:15:19"],

    25 => ["Search by Date", <<<EOT
        Thanks to the sterling efforts of a fine new volunteer (thanks David!), you can now sort any search results either by listing the <a href="https://www.theyworkforyou.com/search/?s=Uzbekistan&o=r">most relevant results first</a>, or <a href="https://www.theyworkforyou.com/search/?s=Uzbekistan&o=d">most recent results first</a>.


        This is good news for those wanting to keep up to date with issues as they pop up in the Commons.

        Next step on search is probably an RSS version of keyword searches. We're still very keen to improve overall relevancy of results - anyone out there fancy lending us a Google Appliance to play with?

        As ever, <a href="/contact/">contact us</a> if you've got any suggestions how we might improve the site, or if you fancy volunteering.
        EOT, "2005-01-25 00:16:09"],

    20 => ["New! Ministerial Statements now included", <<<EOT
        The <a href="https://www.theyworkforyou.com/">TheyWorkForYou.com</a> volunteers are busily crunching their way through the more esoteric corners of Hansard. By way of example, we've just incorporated <a href="https://www.theyworkforyou.com/wms/">Written Ministerial Statements</a> into the site.

        Nope, most of us had never heard of them either, but apparently:

        <i><a href="https://www.theyworkforyou.com/wms/">Written Ministerial Statements</a> were introducted in late 2002 to stop the practice of having "planted" or "inspired" questions designed to elicit Government statements.

        They are just that - statements on a particular topic by a Government Minister.
        </i>

        Well, there you go. You live and learn.

        Next up: Select Committees, which promise to be an order of magnitude more challenging. We'll keep you posted.

        PS We're always on the lookout for new volunteers - <a href="/contact/">contact us</a> if you are keen to help in almost any capacity.
        EOT, "2005-01-17 17:44:47"],

    19 => ["NEW! Westminster Hall debates now available.", <<<EOT
        The volunteers have been busy over the past few days, and we are now one stage closer towards <a href="https://www.theyworkforyou.com/">TheyWorkForYou.com</a> becoming a truly comprehensive record of Parliamentary activity.

        We've included <a href="https://www.theyworkforyou.com/whall/">debates from Westminster Hall</a>, which is a new-ish forum sitting in parallel to the main Commons Chamber. Learn more about it <a href="http://www.explore.parliament.uk/Parliament.aspx?id=10416&glossary=true">here</a>.

        <a href="https://www.theyworkforyou.com/whall/">Westminster Hall Debates</a> were introduced in 1999 with the aim of encouraging constructive rather than confrontational debate between MPs.

        As ever, we'll let you be the judge.

        Next up, parsing all the various House of Commons' Committees... .





        EOT, "2004-12-22 23:47:19"],

    18 => ["RSS feed of your MP's recent appearances", <<<EOT
        Don't forget you can keep track of any MP's recent House of Commons appearances via an RSS newsfeed. You'll find the link in the right-hand column of each individual MP's page.

        For example, the RSS feed of <a href="https://www.theyworkforyou.com/mp/?pid=10508">Barabara Roche's</a> recent appearances in the House of Commons can be found at the following web address:

        <blockquote><a href="https://www.theyworkforyou.com/rss/mp/10508.rdf">https://www.theyworkforyou.com/rss/mp/10508.rdf</a>
        </blockquote>

        RSS is a simple way to publish & distribute content which is frequently updated  (<a href="http://news.bbc.co.uk/1/hi/help/3223484.stm">learn more here</a>.)

        Some enlightened MPs are now incorporating this useful feed of their Parliamentary activity into their websites. See <a href="http://www.richardallan.org.uk/">Richard Allen's</a> website for a good example.
        EOT, "2004-11-27 16:38:41"],

    16 => ["NEW! MPs' Expenses", <<<EOT
        At the bottom of <a href="https://www.theyworkforyou.com/mps/">each MP's page</a>, you can now see how much money your MP has claimed in allowances over the past three years.

        For example, it is good to note that <a href="https://www.theyworkforyou.com/mp/?pid=10508#expenses">Barbara Roche</a>, a North London MP, does not claim excessive travel expenses.

        Bear in mind that proper democracy does cost money, so please think twice before using these data as a stick with which to beat your MP. An "expensive" MP might be providing excellent value for money. And vice versa.
        EOT, "2004-11-08 10:11:14"],

    11 => ["TheyWorkForYou.com Development Wiki now public", <<<EOT
        Shortly after we launched our beta test in June we decided we should also open up access to the wiki we use to help develop TheyWorkForYou.com so that anyone can also have a go, muck in, or just laugh at us.

        Of course, that took far longer than we planned, but having <a href="https://www.theyworkforyou.com/news/archives/2004/07/18/new_full_source_.php">published the source code</a>, we should open up the wiki too.

        We don't like wiki spam, so it will remain passworded, but anyone who wants a browse can tuck in at the <a href="https://www.theyworkforyou.com/wiki/moin.cgi">TheyWorkForYou.com wiki</a>

        username: theyworkforyou
        password: n0vemb3r (n-zero-vemb-three-r)

        (dissemination is fine, but please don't post the link with the login details embedded in the url)

        In case you're wondering what we're up to, well, we're just rousing ourselves for another tilt at completing our vision before the next election is called, so if anyone python or php skills and fancies lending a hand, do <a href="/contact/">contact us</a>.
        EOT, "2004-10-01 23:14:13"],

    8 => ["New! Full Source Code Published", <<<EOT
        <strong>Update:</strong> Code has moved location, updated the URLs and text accordingly.

        We're <em>really</em> keen for others to use both our code and the data feeds we make available to make the UK's Parliament more accessible.

        Most notably, we've published the source code for the front and back end of TheyWorkForYou.com. It's available here:
        <a href="https://secure.mysociety.org/cvstrac/dir?d=mysociety/twfy">https://secure.mysociety.org/cvstrac/dir?d=mysociety/twfy</a> for the front end website, and
        <a href="http://www.knowledgeforge.net/project/ukparse/">http://www.knowledgeforge.net/project/ukparse/</a> for the backend scraper and parser.

        <strong>But there's more!</strong> Below, you'll find links to various other open data feeds and resources we've produced to date:

        <a href="http://ukparse.kforge.net/parlparse/">http://ukparse.kforge.net/parlparse/</a> - XML files of debates / written answers back to June 2001 (House of Commons only - not Westminster Hall); MP id and constituency index files;  how to use the parser source code.

        <a href="http://www.publicwhip.org.uk/project/data.php">http://www.publicwhip.org.uk/project/data.php</a> - MP performance data; voting record matrices.

        <a href="https://www.theyworkforyou.com/rss/mp/10508.rdf">https://www.theyworkforyou.com/rss/mp/10508.rdf</a>
            - Typical MP 'recent appearances' RSS feed, uses person id.
        EOT, "2004-07-18 22:58:23"],

    7 => ["Public Beta Now Live", <<<EOT
        Welcome to the public beta of TheyWorkForYou.com, which launched on Sunday 6th June 2004 at the <a href="http://www.notcon04.com/" title="link to NotCon04 conference website">NotCon04</a> conference.

        We hope you enjoy using the service during its public beta phase, which will last for  a while as we unpick all the bugs and tweak the features.

        We want to know everything: what you like, what you hate, what works, what's broken, what could we do better. The lot. Don't hold back.

        The search engine is our main area of immediate focus - we know we've got months of tuning and tweaking of search results to come. We know enough to know that great search is hard, and that your feedback is crucial.

        In the meantime, please enjoy being the first people to scribble in the margins of Hansard. May you be first of many.

        <a href="/contact/">Send all bug reports (and feature suggestions) to us</a>.

        Finally, a big 'thank you' to everyone who helped test the site during the private beta phase over the past two weeks. Your feedback has been invaluable.

        More than the usuals,

        - <i><a href="https://www.theyworkforyou.com/about/" title="link to About Us page">The TheyWorkForYou.com Volunteers</a></i>
        EOT, "2004-06-06 03:02:53"],

    6 => ["Want to help make us complete?", <<<EOT
        Please remember that this isn't yet a complete record of our MPs' activities in the House of Commons. For that we need to add the transcripts of Select Committee proceedings and a load of other fiddly and esoteric information.

        We also want to add data from before 2001. And there's always the Lords... We yearn to be a complete record.

        But to do this, we will need to ensure our key developers are kept fed. If you're in a sugar daddy frame of mind, we'd be only too pleased to accept donations.

        Just <a href="/contact/">contact us</a>.

        EOT, "2004-05-21 22:15:22"],

    5 => ["Know someone who'd like this website?", <<<EOT
        If you know someone who would appreciate being a beta tester, please <a href="/contact/">contact us</a> with their details.

        Many thanks.
        EOT, "2004-05-21 22:55:24"],

    4 => ["Welcome to our private beta test", <<<EOT
        Hello all, and thank you for helping to road test TheyWorkForYou.com. We hope you enjoy our new baby; we're proud of her, even though she's still somewhat rough around the edges.

        We've just entered our 'closed beta' phase - you'll need a password to access the site until we move into our open beta phase sometime in June.

        Please <a href="/contact/">contact us</a> with your feedback, or you can just add your comments to this blog.

        We want to know everything: what you like, what you hate, what works, what's broken, what could we do better. The lot. Don't hold back.

        The search engine is our main area of immediate focus - we know we've got months of tuning and tweaking of search results to come. We know enough to know that great search is hard, and that your feedback is crucial.

        In the meantime, please enjoy being the first people to scribble in the margins of Hansard. May you be first of many.

        - <em>The TheyWorkForYou.com volunteers</em>
        EOT, "2004-05-21 22:55:24"],
];

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
    return WEBPATH . "news/archives/" . str_replace("-", "/", substr($date, 0, 10)) . "/" . news_format_ref($title);
}

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
