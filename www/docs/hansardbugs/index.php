<?php

$this_page = 'hansard_bugs';
include_once '../../includes/easyparliament/init.php';
$DATA->set_page_metadata($this_page, 'heading','Official Hansard problems');

$PAGE->page_start();
$PAGE->stripe_start();

$PAGE->block_start(array ('title'=>'Things currently noticeable to the user'));
?>

<style type="text/css">
dt strong {
    color: #ff0000;
}
</style>
<dl>

<dt>Missing Written Answers - <strong>still missing as of 6th July</strong></dt>
<dd>The written answers from the 5th June of the Prime Minister, Culture, Media and Sport, Electoral Commission Committee, Church Commissioners, Solicitor-General, and Scotland have disappeared from the official site this morning (9th June), though they were there yesterday. I believe this to be simply a navigation problem (see below). TheyWorkForYou still <a href="/wrans/?d=2006-06-05">has them all in its database</a>.
</dd>

<dt>Written Answers navigation problems - <strong>still happening as of 6th July, and getting worse</strong></dt>
<dd>
<p>The start of one day's Written Answers does not now start at the beginning of a new
page as it always used to, but is adjoined to the end of the previous day.
So the first link on the <a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060606/index/60606-x.htm">6th June index page</a>
actually goes to the last page of the 5th June, and as it's the last page
there's no way to get to the real first page of 6th June answers, without
going back to the index, finding the first question on that next page and clicking it.
</p>
<p>Also, when a new batch of a day's written answers are added, the "Next Section"
link from the last page of the previous batch is not always added,
so it is very hard to navigate through. For example,
<a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0605.htm">http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0605.htm</a>
has no Next Section link to
<a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0607.htm">http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0607.htm</a>
and
<a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0638.htm">http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0638.htm</a>
has no Next Section link to
<a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0673.htm">http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0673.htm</a>.

<p>If I were browsing the 5th June written answers, I would get very confused when I got to the end and found just a few 6th June answers. The same thing seems to apply to most days since the new system began. I personally think it would be better if each day's written answers appeared in their own sections, so that it is very clear.</p>

<p><em>Update:</em> Not only is this still happening, it's getting worse. For example, every page of a subsequent upload of 3rd July written answers is missing "Next Section" links - from <a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060703/text/60703w1405.htm">http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060703/text/60703w1405.htm</a> to <a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060703/text/60703w1408.htm">http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060703/text/60703w1408.htm</a>.
</p>

</dd>

<dt>Division lists - <strong>still happening as of 6th July</strong></dt>
<dd><p>Something odd is happening in the list of names in divisions, that makes it look odd when viewed.
For example, <a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060522/debtext/60522-0283.htm#06052329001881">division 246 on the 22nd of May</a>
is missing a "&lt;br&gt;" between various names:</p>
<ul><li>"Gilroy, Linda" and "Goldsworthy, Julia"
<li>"Roy, Mr. Frank" and "Ruddock, Joan"
<li>"Jack, rh Mr. Michael" and "Jackson, Mr. Stewart"
</ul>
<p>However, on the first version of the debates of 22nd May uploaded, the missing "&lt;br&gt;"s
were in different places, between Hope and Hopkins, Soulsby and Spellar, and Pelling and Penrose.</p>
</dd>

<dt>Missing spaces - <strong>still broken</strong></dt>
<dd>This one needs no explanation: <a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060522/text/60522w0454.htm">http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060522/text/60522w0454.htm</a>
</dd>

<dt>Unicode output - <strong>a bit better!</strong></dt>
<dd>Since 8th May, the text in Hansard has sometimes contained characters encoded in UTF-8 (Unicode),
but the page still says it in ISO-8859-1 (or the Windows variant). Added to this, somewhere along the
line certain symbols are rewritten (e.g. the copyright symbol changes from &#169; to (c)) which breaks
the UTF-8 encoding, and means "caf&eacute;" becomes "caf&Atilde;(c)", for example:
<a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060511/debtext/60511-0136.htm">column 580 on 11th May 2006</a>.

<p><em>Update:</em> Yesterday's Lords used the correct UTF-8 encoding of an e-acute, yay!</p>
</dd>

<dt>Michael Foster as a teller</dt>
<dd>Michael Foster no longer appears to have his constituency listed in divisions where he's a teller, so you don't know which Michael Foster it is (okay, so you can work it out, but it's much easier).
</dd>

<dt>Heading importance</dt>
<dd>There is no real way to tell whether something is a major or a minor heading,
and it is impossible to tell programmatically when Oral Questions finish. Major
headings used to appear in all capitals to differentiate themselves from lesser
headings - this wasn't perfect, but was better than now.
</dd>

<dt>Ministerial Statement tables - <strong>still a problem</strong></dt>
<dd>Compare <a href="http://ukparse.kforge.net/parldata/cmpages/wms/ministerial2006-05-08a.html">old style</a> with <a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060508/wmstext/60508m01.htm">new style</a> - the table has gone.

<dt>Volume 428 - <strong>fixed 20th July!</strong></dt>
<dd>Lots went wrong when volume 428 was uploaded in January 2006, replacing the Daily Editions for dates at the end of 2004 with Bound Volume editions. All the Oral Questions
have disappeared from the 8th, 9th and 13th December 2004. On the 15th
December, the Oral Answers have gone and the debates remaining are actually
those from the 14th December, even though the date says the 15th. The 16th
December starts off with the correct Oral Answers, but then shows the
debates from the 15th, and so does the 20th, showing the debates from the
16th. See how the column numbering goes from 1909 to 1793 on
<a href="http://www.publications.parliament.uk/pa/cm200405/cmhansrd/vo041220/debindx/41220-x.htm">http://www.publications.parliament.uk/pa/cm200405/cmhansrd/vo041220/debindx/41220-x.htm</a>.
The 21st debates return to behaving like the 15th, with no Oral Answers, and
showing the debates from the 20th.
There are a couple of other minor issues, but that's the important stuff. :)
</dd>

<dt>Written Answer columns</dt>
<dd>Note that although the column numbering has got up to 214W, the footer still says column 153W: <a href="http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0617.htm">http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm060605/text/60605w0617.htm</a>.
</dd>

</dl>

<?php
$PAGE->block_end();
$PAGE->block_start(array ('title'=>'Things not noticeable to the user'));
?>

<ul>
<li>Some XHTML is being output when the pages are in HTML (e.g. &lt;hr/&gt; and even &lt;br&gt;&lt;/br&gt;). Not very important.
</ul>

<?php
$PAGE->block_end();

$includes = array(
);
$PAGE->stripe_end($includes);
$PAGE->page_end();
