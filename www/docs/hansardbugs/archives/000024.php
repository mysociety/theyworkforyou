<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>Hansard Bugs Blog: weeklyupdate has some fun</title>

<link rel="stylesheet" href="http://www.theyworkforyou.com/hansardbugs/styles-site.css" type="text/css" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.theyworkforyou.com/hansardbugs/index.rdf" />

<link rel="start" href="http://www.theyworkforyou.com/hansardbugs/" title="Home" />
<link rel="prev" href="http://www.theyworkforyou.com/hansardbugs/archives/000022.php" title="This week's problems" />

<link rel="next" href="http://www.theyworkforyou.com/hansardbugs/archives/000027.php" title="24th-26th January" />


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
<a href="http://www.theyworkforyou.com/hansardbugs/archives/000022.php">&laquo; This week's problems</a> |

<a href="http://www.theyworkforyou.com/hansardbugs/">Main</a>
| <a href="http://www.theyworkforyou.com/hansardbugs/archives/000027.php">24th-26th January &raquo;</a>

</div>

</div>


<div class="blog">

<h2 class="date">January 23, 2005</h2>

<div class="blogbody">

<h3 class="title">weeklyupdate has some fun</h3>

<p>weeklyupdate is a script that runs weekly (Sunday mornings, 4.23am) over the whole of Hansard back to June 2001, until this week simply to spot when Hansard do a "daily" to "bound volume" edition switch (this is when they make big changes to the text to bring it to its final edition, and change the URLs to have "vo" instead of "cm" in them, thereby breaking all existing links to the pages), and to try and parse anything from long ago we've forgotten about.</p>

<p>This week, I added the diff functionality I mentioned in the previous post. Which meant this week weeklyupdate went and redownloaded the <em>whole</em> of Hansard back to June 2001, and checked it all against what we had to see what had changed. I was somewhat surprised when I did not receive a gigabyte email this morning...</p>

<a name="more"></a>
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

<span class="posted">Posted by matthew at January 23, 2005 09:24 PM

<br /></span>

</div>


<div class="comments-head"><a name="comments"></a>Comments</div>





</div>
</div>
</body>
</html>
