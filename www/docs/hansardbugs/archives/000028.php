<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>Hansard Bugs Blog: Volume 423</title>

<link rel="stylesheet" href="http://www.theyworkforyou.com/hansardbugs/styles-site.css" type="text/css" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.theyworkforyou.com/hansardbugs/index.rdf" />

<link rel="start" href="http://www.theyworkforyou.com/hansardbugs/" title="Home" />
<link rel="prev" href="http://www.theyworkforyou.com/hansardbugs/archives/000027.php" title="24th-26th January" />

<link rel="next" href="http://www.theyworkforyou.com/hansardbugs/archives/000029.php" title="Quandry" />


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
<a href="http://www.theyworkforyou.com/hansardbugs/archives/000027.php">&laquo; 24th-26th January</a> |

<a href="http://www.theyworkforyou.com/hansardbugs/">Main</a>
| <a href="http://www.theyworkforyou.com/hansardbugs/archives/000029.php">Quandry &raquo;</a>

</div>

</div>


<div class="blog">

<h2 class="date">February 07, 2005</h2>

<div class="blogbody">

<h3 class="title">Volume 423</h3>

<p>Volume 423 of Hansard is out - http://www.publications.parliament.uk/pa/cm/cmvol423.htm - so this weekend every day from 2004-06-28 to 2004-07-16 changed, sometimes dramatically, and had to be reparsed. These changes are normally spelling corrections and the like, normally for the better (e.g. lots of erroneous "Sitting suspended" headings in the Westminster Hall debates have become plain paragraphs, various people's names are corrected), but not always (e.g. some Bill Clause debate headings have mysteriously become plain paragraphs, so are now being tacked on to the end of the previous speech - I've added a hack for those, hopefully).</p>

<p>Also, another batch of Thursday's Written Answers only appeared this morning - I think this has become a regular thing for them.</p>

<a name="more"></a>


<span class="posted">Posted by matthew at February  7, 2005 11:49 PM

<br /></span>

</div>


<div class="comments-head"><a name="comments"></a>Comments</div>





</div>
</div>
</body>
</html>
