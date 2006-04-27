<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />

<title>Hansard Bugs Blog: Last week</title>

<link rel="stylesheet" href="http://www.theyworkforyou.com/hansardbugs/styles-site.css" type="text/css" />
<link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.theyworkforyou.com/hansardbugs/index.rdf" />

<link rel="start" href="http://www.theyworkforyou.com/hansardbugs/" title="Home" />

<link rel="next" href="http://www.theyworkforyou.com/hansardbugs/archives/000022.php" title="This week's problems" />


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
    rdf:about="http://www.theyworkforyou.com/hansardbugs/archives/000023.php"
    trackback:ping="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi/22"
    dc:title="Last week"
    dc:identifier="http://www.theyworkforyou.com/hansardbugs/archives/000023.php"
    dc:subject=""
    dc:description="<![CDATA[Woo, I have a blog! All right, it's not mine, it's to fill with long, rambling, and probably boring posts about errors in the Official Parliamentary Record. So without further ado... 10th - Some rogue "&amp;nbsp;" in some headings, a...]]>"
    dc:creator="matthew"
    dc:date="2005-01-18T23:09:30+00:00" />
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

<a href="http://www.theyworkforyou.com/hansardbugs/">Main</a>
| <a href="http://www.theyworkforyou.com/hansardbugs/archives/000022.php">This week's problems &raquo;</a>

</div>

</div>


<div class="blog">

<h2 class="date">January 18, 2005</h2>

<div class="blogbody">

<h3 class="title">Last week</h3>

<p>Woo, I have a blog! All right, it's not mine, it's to fill with long, rambling, and probably boring posts about errors in the Official Parliamentary Record. So without further ado...</p>

<p>10th - Some rogue "&amp;nbsp;" in some headings, a missing start of question in written answers, and a random "23" in someone's name</p>

<p>11th - Today was the last day I had to manually remove the "&lt;/ul&gt;&lt;/ul&gt;&lt;/ul&gt;" gumph from the end of a debate day, as I added an automatic check for it. :) No actual problems besides this.</p>

<p>12th - a misplaced "&amp;#151;" in debates</p>

<p>13th - written ministerial statements thought it was February, and had a missing <b></b> aroud the DWP minister. Sorry about the delay getting written answers up: I got confused by Hansard's parser saying some text (which was actually quoted from a letter) was a new question, and trying to work around it before spotting what it actually was.</p>

<p>Also, we appear to be missing the end of written answers sometimes; will investigate cause and solution.</p>

<a name="more"></a>


<span class="posted">Posted by matthew at January 18, 2005 11:09 PM
| <a href="http://live.theyworkforyou.com/cgi-bin/mt-tb.cgi?__mode=view&amp;entry_id=23" onclick="OpenTrackback(this.href); return false">TrackBack</a>

<br /></span>

</div>


<div class="comments-head"><a name="comments"></a>Comments</div>




<div class="comments-head">Post a comment</div>

<div class="comments-body">
<form method="post" action="http://live.theyworkforyou.com/cgi-bin/mt-comments.cgi" name="comments_form" onsubmit="if (this.bakecookie[0].checked) rememberMe(this)">
<input type="hidden" name="static" value="1" />
<input type="hidden" name="entry_id" value="23" />

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
