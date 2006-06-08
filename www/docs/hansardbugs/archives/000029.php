<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>Hansard Bugs Blog: Quandry</title>

<link rel="stylesheet" href="http://www.theyworkforyou.com/hansardbugs/styles-site.css" type="text/css" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.theyworkforyou.com/hansardbugs/index.rdf" />

<link rel="start" href="http://www.theyworkforyou.com/hansardbugs/" title="Home" />
<link rel="prev" href="http://www.theyworkforyou.com/hansardbugs/archives/000028.php" title="Volume 423" />



<script type="text/javascript" language="javascript">
<!--

function OpenTrackback (c) {
    window.open(c,
                    'trackback',
                    'width=480,height=480,scrollbars=yes,status=yes');
}

var HOST = 'www.theyworkforyou.com';

// Copyright (c) 1996-1997 Athenia Associates.
// http://www.webreference.com/js/
// License is granted if and only if this entire
// copyright notice is included. By Tomer Shiran.

function setCookie (name, value, expires, path, domain, secure) {
    var curCookie = name + "=" + escape(value) + ((expires) ? "; expires=" + expires.toGMTString() : "") + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + ((secure) ? "; secure" : "");
    document.cookie = curCookie;
}

function getCookie (name) {
    var prefix = name + '=';
    var c = document.cookie;
    var nullstring = '';
    var cookieStartIndex = c.indexOf(prefix);
    if (cookieStartIndex == -1)
        return nullstring;
    var cookieEndIndex = c.indexOf(";", cookieStartIndex + prefix.length);
    if (cookieEndIndex == -1)
        cookieEndIndex = c.length;
    return unescape(c.substring(cookieStartIndex + prefix.length, cookieEndIndex));
}

function deleteCookie (name, path, domain) {
    if (getCookie(name))
        document.cookie = name + "=" + ((path) ? "; path=" + path : "") + ((domain) ? "; domain=" + domain : "") + "; expires=Thu, 01-Jan-70 00:00:01 GMT";
}

function fixDate (date) {
    var base = new Date(0);
    var skew = base.getTime();
    if (skew > 0)
        date.setTime(date.getTime() - skew);
}

function rememberMe (f) {
    var now = new Date();
    fixDate(now);
    now.setTime(now.getTime() + 365 * 24 * 60 * 60 * 1000);
    setCookie('mtcmtauth', f.author.value, now, '', HOST, '');
    setCookie('mtcmtmail', f.email.value, now, '', HOST, '');
    setCookie('mtcmthome', f.url.value, now, '', HOST, '');
}

function forgetMe (f) {
    deleteCookie('mtcmtmail', '', HOST);
    deleteCookie('mtcmthome', '', HOST);
    deleteCookie('mtcmtauth', '', HOST);
    f.email.value = '';
    f.author.value = '';
    f.url.value = '';
}

//-->
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
<a href="http://www.theyworkforyou.com/hansardbugs/archives/000028.php">&laquo; Volume 423</a> |

<a href="http://www.theyworkforyou.com/hansardbugs/">Main</a>

</div>

</div>


<div class="blog">

<h2 class="date">February 09, 2005</h2>

<div class="blogbody">

<h3 class="title">Quandry</h3>

<p>Our scraper runs at 8:05am every morning, followed immediately by the parser, database-adder (which puts it live) and search-indexer (which adds new entries to the search index). Now, sometimes (rarely) this all goes fine. Sometimes (like today) Hansard isn't up until a few minutes after our scraper has run, and we don't get anything, whoops. And sometimes (most common), the scraping goes fine, but there are fiddly flaws in their HTML which prevent the parser parsing some or all of the new content.</p>

<p>Currently, I appear to be the only one who fixes things that are wrong and puts up the latest stuff, and if I go to work early (as I did this morning), I can't rerun the scraper until I get home. Which means a whole day when content isn't up that could/should have been. Which isn't good when we want to be running email alerts on new content very soon.</p>

<p>One solution (at least to them putting content up a bit late) is simply to run the update (or an even more cut down version of it) again later in the day (midday or something like that). But this doesn't stop the second problem, of the parser not parsing the new stuff. Our parser is quite honed nowadays, so the only problems generally appear to be a single misspelling (a missing space or dot in a time, for example), a missing "To ask" statement at the beginning of a Written Question, missing indication of a new speaker (&lt;b&gt;&lt;/b&gt; around the speaker's name), and missing "(1)" in a multi-part written question. My current feeling is that getting new stuff live is more important than any of these, so am thinking of changing the parser to pass some of these failures (rather than die) and then email me so I can update later on (which would only be a minor change). This should certainly be possible for missing text errors; harder for missing speaker indication. We shall see.</p>

<a name="more"></a>


<span class="posted">Posted by matthew at February  9, 2005 06:52 PM

<br /></span>

</div>


<div class="comments-head"><a name="comments"></a>Comments</div>





</div>
</div>
</body>
</html>
