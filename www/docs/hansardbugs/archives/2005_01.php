<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>Hansard Bugs Blog: January 2005 Archives</title>

<link rel="stylesheet" href="http://www.theyworkforyou.com/hansardbugs/styles-site.css" type="text/css" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.theyworkforyou.com/hansardbugs/index.rdf" />
<link rel="start" href="http://www.theyworkforyou.com/hansardbugs/" title="Home" />

<link rel="next" href="http://www.theyworkforyou.com/hansardbugs/archives/2005_02.php" title="February 2005" />


<script language="javascript" type="text/javascript">
function OpenComments (c) {
    window.open(c,
                    'comments',
                    'width=480,height=480,scrollbars=yes,status=yes');
}

function OpenTrackback (c) {
    window.open(c,
                    'trackback',
                    'width=480,height=480,scrollbars=yes,status=yes');
}
</script>

</head>

<body>	

<div id="banner">
<h1><a href="http://www.theyworkforyou.com/hansardbugs/" accesskey="1">Hansard Bugs Blog</a></h1>
<span class="description">A collection of bugs and errors found by the TheyWorkForYou.com volunteers in the content and code of Hansard's official website.</span>
</div>

<div id="container">

<div class="blog">

<div id="menu">

<a href="http://www.theyworkforyou.com/hansardbugs/">Main</a>
| <a href="http://www.theyworkforyou.com/hansardbugs/archives/2005_02.php">February 2005 &raquo;</a>

</div>

</div>

<div class="blog">
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"
         xmlns:dc="http://purl.org/dc/elements/1.1/">
<rdf:Description
    rdf:about="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php#000027"
    trackback:ping="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi/26"
    dc:title="24th-26th January"
    dc:identifier="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php#000027"
    dc:subject=""
    dc:description="<![CDATA[Monday had a missing &lt;b&gt;&lt;/b&gt; around a speaker's name (hmm, maybe we should check if the first two words of a speech are actually "Title Surname:" - that might actually work...), and a couple of questions had missing bits of...]]>"
    dc:creator="matthew"
    dc:date="2005-01-28T00:04:54+00:00" />
</rdf:RDF>
-->


<h2 class="date">January 28, 2005</h2>


<div class="blogbody">
<a name="000027"></a>
<h3 class="title">24th-26th January</h3>

<p>Monday had a missing &lt;b&gt;&lt;/b&gt; around a speaker's name (hmm, maybe we should check if the first two words of a speech are actually "Title Surname:" - that might actually work...), and a couple of questions had missing bits of the "To ask" section.</p>

<p>Tuesday went fine, but they only put the written answers up at 8.22am, and also added another lot of written answers to Monday at the same time. As our parser runs at 8.05am, it didn't pick these up until Thursday morning, when my new diff checker spotted the additions and seamlessly added them (yay me). We will probably make some changes and run the automatic jobs much more frequently to catch these sooner - hopefully using HTTP cacheing headers to save on bandwidth all round.</p>



<div class="posted">
	Posted by matthew at <a href="http://www.theyworkforyou.com/hansardbugs/archives/000027.php">12:04 AM</a>
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi?entry_id=27" onclick="OpenComments(this.href); return false">Comments (1)</a>
	
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi?__mode=view&amp;entry_id=27" onclick="OpenTrackback(this.href); return false">TrackBack</a>
	
</div>

</div>

<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"
         xmlns:dc="http://purl.org/dc/elements/1.1/">
<rdf:Description
    rdf:about="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php#000024"
    trackback:ping="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi/23"
    dc:title="weeklyupdate has some fun"
    dc:identifier="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php#000024"
    dc:subject=""
    dc:description="weeklyupdate is a script that runs weekly (Sunday mornings, 4.23am) over the whole of Hansard back to June 2001, until this week simply to spot when Hansard do a &quot;daily&quot; to &quot;bound volume&quot; edition switch (this is when they make..."
    dc:creator="matthew"
    dc:date="2005-01-23T21:24:38+00:00" />
</rdf:RDF>
-->


<h2 class="date">January 23, 2005</h2>


<div class="blogbody">
<a name="000024"></a>
<h3 class="title">weeklyupdate has some fun</h3>

<p>weeklyupdate is a script that runs weekly (Sunday mornings, 4.23am) over the whole of Hansard back to June 2001, until this week simply to spot when Hansard do a "daily" to "bound volume" edition switch (this is when they make big changes to the text to bring it to its final edition, and change the URLs to have "vo" instead of "cm" in them, thereby breaking all existing links to the pages), and to try and parse anything from long ago we've forgotten about.</p>

<p>This week, I added the diff functionality I mentioned in the previous post. Which meant this week weeklyupdate went and redownloaded the <em>whole</em> of Hansard back to June 2001, and checked it all against what we had to see what had changed. I was somewhat surprised when I did not receive a gigabyte email this morning...</p>

<p>Here, for anyone who might care, is what had changed:</p>

<p><h4>Written Answers</h4></p>

<p>For 2004-12-06, 2004-11-18, 2004-11-17, 2004-11-01, 2004-10-26, 2004-10-25, 2004-10-18, 2004-10-12, 2004-10-11, 2004-09-08, 2004-07-22, 2004-07-21, 2004-07-20, and 2004-07-19, we were missing written answers, all from the end (as in, our scraper had not downloaded all of the pages of written answers for some reason). Still don't know why, but we have them now. Also, Lord Filkin decided to answer one of the new answers on 2004-11-18, which is annoying, as we don't have Lords in a database yet.</p>

<p>2004-10-04: A table about primary schools became a table about secondary schools<br />
2004-03-12 and 2003-07-17: A very big reordering of the text, don't know why. But the parser coped magnificantly.</p>

<p><h4>Debates</h4></p>

<p>2004-12-02: "to about $200 an ounce" became "by about $200 an ounce". And the Solicitor-General got asked another question.<br />
2004-11-30: They realised they'd forgotten to say when someone had stopped talking and someone else had began, which was amusing as it used to have a Lib Dem MP saying how people "thank me as a Labour MP". This change reflowed a number of columns later on the day, which meant some tedious renumbering so that our URLs stayed consistent.<br />
2004-11-29: Mr Khabra's speech changed (in fact, this correction can be found the following day at http://www.theyworkforyou.com/debates/?id=2004-11-30.611.4 )<br />
2004-11-18: I don't like how they present the end of the session of Parliament, so had changed it manually. Obviously, the redownload overrode that. I have applied my change as a patch, which is the proper way to do it to ensure it is not lost.<br />
2004-11-08: Jane Whitaker now has her name spelt, I presume, correctly<br />
2004-10-25: Kim Howell's amendment has been changed<br />
2004-07-06: Buxton has become Butterton</p>

<p>And that was it! Very impressed at how it all held up and worked together. :)</p>


<div class="posted">
	Posted by matthew at <a href="http://www.theyworkforyou.com/hansardbugs/archives/000024.php">09:24 PM</a>
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi?entry_id=24" onclick="OpenComments(this.href); return false">Comments (7)</a>
	
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi?__mode=view&amp;entry_id=24" onclick="OpenTrackback(this.href); return false">TrackBack</a>
	
</div>

</div>

<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"
         xmlns:dc="http://purl.org/dc/elements/1.1/">
<rdf:Description
    rdf:about="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php#000022"
    trackback:ping="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi/21"
    dc:title="This week&apos;s problems"
    dc:identifier="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php#000022"
    dc:subject=""
    dc:description="This week was very uneventful. Written answers on the 17th were missing marking Mr Kilfoyle as a speaker and &quot;ask the Secretary of State for Foreign and Commonwealth Affairs&quot; at the start of a question. Written ministerial statements said it..."
    dc:creator="matthew"
    dc:date="2005-01-21T23:03:56+00:00" />
</rdf:RDF>
-->


<h2 class="date">January 21, 2005</h2>


<div class="blogbody">
<a name="000022"></a>
<h3 class="title">This week's problems</h3>

<p>This week was very uneventful. Written answers on the 17th were missing marking Mr Kilfoyle as a speaker and "ask the Secretary of State for Foreign and Commonwealth Affairs" at the start of a question. Written ministerial statements said it was 2004. One multi-part written answer question was missing a "(1)" on the 18th, the 19th was totally problem free, and on the 20th a simple misspelling of AFFAIRS with 3 Fs.</p>

<p>I changed our scraper to diff downloaded stuff, which means we should soon be able to spot any changes they make to old entries, besides the obvious daily to bound volume edition change.</p>



<div class="posted">
	Posted by matthew at <a href="http://www.theyworkforyou.com/hansardbugs/archives/000022.php">11:03 PM</a>
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi?entry_id=22" onclick="OpenComments(this.href); return false">Comments (7)</a>
	
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi?__mode=view&amp;entry_id=22" onclick="OpenTrackback(this.href); return false">TrackBack</a>
	
</div>

</div>

<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"
         xmlns:dc="http://purl.org/dc/elements/1.1/">
<rdf:Description
    rdf:about="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php#000023"
    trackback:ping="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi/22"
    dc:title="Last week"
    dc:identifier="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php#000023"
    dc:subject=""
    dc:description="<![CDATA[Woo, I have a blog! All right, it's not mine, it's to fill with long, rambling, and probably boring posts about errors in the Official Parliamentary Record. So without further ado... 10th - Some rogue "&amp;nbsp;" in some headings, a...]]>"
    dc:creator="matthew"
    dc:date="2005-01-18T23:09:30+00:00" />
</rdf:RDF>
-->


<h2 class="date">January 18, 2005</h2>


<div class="blogbody">
<a name="000023"></a>
<h3 class="title">Last week</h3>

<p>Woo, I have a blog! All right, it's not mine, it's to fill with long, rambling, and probably boring posts about errors in the Official Parliamentary Record. So without further ado...</p>

<p>10th - Some rogue "&amp;nbsp;" in some headings, a missing start of question in written answers, and a random "23" in someone's name</p>

<p>11th - Today was the last day I had to manually remove the "&lt;/ul&gt;&lt;/ul&gt;&lt;/ul&gt;" gumph from the end of a debate day, as I added an automatic check for it. :) No actual problems besides this.</p>

<p>12th - a misplaced "&amp;#151;" in debates</p>

<p>13th - written ministerial statements thought it was February, and had a missing <b></b> aroud the DWP minister. Sorry about the delay getting written answers up: I got confused by Hansard's parser saying some text (which was actually quoted from a letter) was a new question, and trying to work around it before spotting what it actually was.</p>

<p>Also, we appear to be missing the end of written answers sometimes; will investigate cause and solution.</p>



<div class="posted">
	Posted by matthew at <a href="http://www.theyworkforyou.com/hansardbugs/archives/000023.php">11:09 PM</a>
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi?entry_id=23" onclick="OpenComments(this.href); return false">Comments (0)</a>
	
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi?__mode=view&amp;entry_id=23" onclick="OpenTrackback(this.href); return false">TrackBack</a>
	
</div>

</div>


</div>
</div>

</body>
</html>
