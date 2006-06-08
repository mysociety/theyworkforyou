<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>Hansard Bugs Blog: February 2005 Archives</title>

<link rel="stylesheet" href="http://www.theyworkforyou.com/hansardbugs/styles-site.css" type="text/css" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.theyworkforyou.com/hansardbugs/index.rdf" />
<link rel="start" href="http://www.theyworkforyou.com/hansardbugs/" title="Home" />
<link rel="prev" href="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php" title="January 2005" />



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
<a href="http://www.theyworkforyou.com/hansardbugs/archives/2005_01.php">&laquo; January 2005</a> |

<a href="http://www.theyworkforyou.com/hansardbugs/">Main</a>

</div>

</div>

<div class="blog">


<h2 class="date">February 09, 2005</h2>


<div class="blogbody">
<a name="000029"></a>
<h3 class="title">Quandry</h3>

<p>Our scraper runs at 8:05am every morning, followed immediately by the parser, database-adder (which puts it live) and search-indexer (which adds new entries to the search index). Now, sometimes (rarely) this all goes fine. Sometimes (like today) Hansard isn't up until a few minutes after our scraper has run, and we don't get anything, whoops. And sometimes (most common), the scraping goes fine, but there are fiddly flaws in their HTML which prevent the parser parsing some or all of the new content.</p>

<p>Currently, I appear to be the only one who fixes things that are wrong and puts up the latest stuff, and if I go to work early (as I did this morning), I can't rerun the scraper until I get home. Which means a whole day when content isn't up that could/should have been. Which isn't good when we want to be running email alerts on new content very soon.</p>

<p>One solution (at least to them putting content up a bit late) is simply to run the update (or an even more cut down version of it) again later in the day (midday or something like that). But this doesn't stop the second problem, of the parser not parsing the new stuff. Our parser is quite honed nowadays, so the only problems generally appear to be a single misspelling (a missing space or dot in a time, for example), a missing "To ask" statement at the beginning of a Written Question, missing indication of a new speaker (&lt;b&gt;&lt;/b&gt; around the speaker's name), and missing "(1)" in a multi-part written question. My current feeling is that getting new stuff live is more important than any of these, so am thinking of changing the parser to pass some of these failures (rather than die) and then email me so I can update later on (which would only be a minor change). This should certainly be possible for missing text errors; harder for missing speaker indication. We shall see.</p>



<div class="posted">
	Posted by matthew at <a href="http://www.theyworkforyou.com/hansardbugs/archives/000029.php">06:52 PM</a>
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi?entry_id=29" onclick="OpenComments(this.href); return false">Comments (0)</a>
	
	
</div>

</div>



<h2 class="date">February 07, 2005</h2>


<div class="blogbody">
<a name="000028"></a>
<h3 class="title">Volume 423</h3>

<p>Volume 423 of Hansard is out - http://www.publications.parliament.uk/pa/cm/cmvol423.htm - so this weekend every day from 2004-06-28 to 2004-07-16 changed, sometimes dramatically, and had to be reparsed. These changes are normally spelling corrections and the like, normally for the better (e.g. lots of erroneous "Sitting suspended" headings in the Westminster Hall debates have become plain paragraphs, various people's names are corrected), but not always (e.g. some Bill Clause debate headings have mysteriously become plain paragraphs, so are now being tacked on to the end of the previous speech - I've added a hack for those, hopefully).</p>

<p>Also, another batch of Thursday's Written Answers only appeared this morning - I think this has become a regular thing for them.</p>



<div class="posted">
	Posted by matthew at <a href="http://www.theyworkforyou.com/hansardbugs/archives/000028.php">11:49 PM</a>
		| <a href="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi?entry_id=28" onclick="OpenComments(this.href); return false">Comments (0)</a>
	
	
</div>

</div>


</div>
</div>

</body>
</html>
