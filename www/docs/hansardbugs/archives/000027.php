<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>Hansard Bugs Blog: 24th-26th January</title>

<link rel="stylesheet" href="http://www.theyworkforyou.com/hansardbugs/styles-site.css" type="text/css" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.theyworkforyou.com/hansardbugs/index.rdf" />

<link rel="start" href="http://www.theyworkforyou.com/hansardbugs/" title="Home" />
<link rel="prev" href="http://www.theyworkforyou.com/hansardbugs/archives/000024.php" title="weeklyupdate has some fun" />

<link rel="next" href="http://www.theyworkforyou.com/hansardbugs/archives/000028.php" title="Volume 423" />


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

<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/"
         xmlns:dc="http://purl.org/dc/elements/1.1/">
<rdf:Description
    rdf:about="http://www.theyworkforyou.com/hansardbugs/archives/000027.php"
    trackback:ping="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi/26"
    dc:title="24th-26th January"
    dc:identifier="http://www.theyworkforyou.com/hansardbugs/archives/000027.php"
    dc:subject=""
    dc:description="<![CDATA[Monday had a missing &lt;b&gt;&lt;/b&gt; around a speaker's name (hmm, maybe we should check if the first two words of a speech are actually "Title Surname:" - that might actually work...), and a couple of questions had missing bits of...]]>"
    dc:creator="matthew"
    dc:date="2005-01-28T00:04:54+00:00" />
</rdf:RDF>
-->




</head>

<body>

<div id="banner">
<h1><a href="http://www.theyworkforyou.com/hansardbugs/" accesskey="1">Hansard Bugs Blog</a></h1>
<span class="description">A collection of bugs and errors found by the TheyWorkForYou.com volunteers in the content and code of Hansard's official website.</span>
</div>

<div id="container">

<div class="blog">

<div id="menu">
<a href="http://www.theyworkforyou.com/hansardbugs/archives/000024.php">&laquo; weeklyupdate has some fun</a> |

<a href="http://www.theyworkforyou.com/hansardbugs/">Main</a>
| <a href="http://www.theyworkforyou.com/hansardbugs/archives/000028.php">Volume 423 &raquo;</a>

</div>

</div>


<div class="blog">

<h2 class="date">January 28, 2005</h2>

<div class="blogbody">

<h3 class="title">24th-26th January</h3>

<p>Monday had a missing &lt;b&gt;&lt;/b&gt; around a speaker's name (hmm, maybe we should check if the first two words of a speech are actually "Title Surname:" - that might actually work...), and a couple of questions had missing bits of the "To ask" section.</p>

<p>Tuesday went fine, but they only put the written answers up at 8.22am, and also added another lot of written answers to Monday at the same time. As our parser runs at 8.05am, it didn't pick these up until Thursday morning, when my new diff checker spotted the additions and seamlessly added them (yay me). We will probably make some changes and run the automatic jobs much more frequently to catch these sooner - hopefully using HTTP cacheing headers to save on bandwidth all round.</p>

<a name="more"></a>


<span class="posted">Posted by matthew at January 28, 2005 12:04 AM
| <a href="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi?__mode=view&amp;entry_id=27" onclick="OpenTrackback(this.href); return false">TrackBack</a>

<br /></span>

</div>


<div class="comments-head"><a name="comments"></a>Comments</div>

<div class="comments-body">
<p>Save money with Sierra Trading Post promotions</p>
<span class="comments-post">Posted by: <a href="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi?__mode=red&amp;id=29">Sierra Trading Post promotions</a> at February 28, 2005 07:39 AM</span>
</div>



<div class="comments-head">Post a comment</div>

<div class="comments-body">
<form method="post" action="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi" name="comments_form" onsubmit="if (this.bakecookie[0].checked) rememberMe(this)">
<input type="hidden" name="static" value="1" />
<input type="hidden" name="entry_id" value="27" />

<div style="width:180px; padding-right:15px; margin-right:15px; float:left; text-align:left; border-right:1px dotted #bbb;">
	<label for="author">Name:</label><br />
	<input tabindex="1" id="author" name="author" /><br /><br />

	<label for="email">Email Address:</label><br />
	<input tabindex="2" id="email" name="email" /><br /><br />

	<label for="url">URL:</label><br />
	<input tabindex="3" id="url" name="url" /><br /><br />
</div>

Remember personal info?<br />
<input type="radio" id="bakecookie" name="bakecookie" /><label for="bakecookie">Yes</label><input type="radio" id="forget" name="bakecookie" onclick="forgetMe(this.form)" value="Forget Info" style="margin-left: 15px;" /><label for="forget">No</label><br style="clear: both;" />

<label for="text">Comments:</label><br />
<textarea tabindex="4" id="text" name="text" rows="10" cols="50"></textarea><br /><br />

<input type="submit" name="preview" value="&nbsp;Preview&nbsp;" />
<input style="font-weight: bold;" type="submit" name="post" value="&nbsp;Post&nbsp;" /><br /><br />

</form>

<script type="text/javascript" language="javascript">
<!--
document.comments_form.email.value = getCookie("mtcmtmail");
document.comments_form.author.value = getCookie("mtcmtauth");
document.comments_form.url.value = getCookie("mtcmthome");
if (getCookie("mtcmtauth")) {
    document.comments_form.bakecookie[0].checked = true;
} else {
    document.comments_form.bakecookie[1].checked = true;
}
//-->
</script>
</div>


</div>
</div>
</body>
</html>
