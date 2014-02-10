<?php
include_once '../../includes/easyparliament/init.php';
require_once 'share.php';

$this_page = 'campaign';
$PAGE->page_start();
$PAGE->stripe_start();
freeourbills_styles();

?>

<h2>Free our Bills!</h2>

<p id="free_our_bills_banner">The Nice Polite Campaign to Gently Encourage
Parliament to Publish Bills in a 21st Century Way, Please. Now.</p>

<h3>Details of the technical changes we want Parliament to make to the way
it publishes bills.</h3>

<p>We would like Bills, and bill-related data, to be published in a structured
machine-readable way, as soon as is possible after the data has been generated.

<ol>
<li><a href="#1">How are bills published online at the moment?</a>
<li><a href="#2">What are the problems with the current system?</a>
<li><a href="#3">What are the solutions that "Free Our Bills" proposes?</a>
<li><a href="#4">What things could any programmer do if these changes were made?</a>
</ol>

<h3><a name="1"></a>How are bills published online at the moment?</h3>

<p>As bills are written they go through various stages. Towards the very
end of the process, they are dumped out in a format that is sent to
the printers, and which is converted to HTML so that it can be put up
on the Parliamentary website. Daily amendment lists, or proceedings of
Public Bill Committees and the like, are treated similarly. This is
a very complicated process with lots of input from many different places.</p>

<p>Currently, <a href="http://services.parliament.uk/bills/">http://services.parliament.uk/bills/</a>
is Parliament's main page for bills, with the
<a href="http://services.parliament.uk/bills/2008-09/health.html">Health Bill</a> as an example bill
&ndash; click the Show links to see how much information there is generated about
this bill alone.

<h3><a name="2"></a>What are the problems with the current system?</h3>

<p>This is easily illustrated with an example. As of writing, the Health Bill has
just finished its Committee stage; here is <a href="http://www.publications.parliament.uk/pa/cm200809/cmbills/097/09097.i-iii.html">the Bill as introduced to the House of Commons</a>. Now here is
<a href="http://www.publications.parliament.uk/pa/cm200809/cmbills/097/amend/pbc0970615m.63-69.html">the list of amendments for 16th June</a>, the first day the Committee met.
As a first question, can you tell me what Clause 2 would look like if all Stephen O'Brien's
amendments were accepted? Or what Sandra Gidley's Clause 2 amendment does?
The
<a href="http://www.publications.parliament.uk/pa/cm200809/cmpublic/health/090616/am/90616s01.htm">proceedings of the first meeting</a> are elsewhere, as is the <a href="http://www.publications.parliament.uk/pa/cm200809/cmbills/097/pro0971606p.1-6.html">summary of proceedings</a> &ndash; can you tell me what Clause 2 looked like
given the proceedings as to which amendments were made or withdrawn?

<h3><a name="3"></a>What are the solutions that "Free Our Bills" proposes?</h3>

<p>In an ideal world, we would like Bills (and related instruments such as amendment
lists and Public Bill Committee debates) to be published in a structured data
format, with all relevant metadata, as soon as is possible. This doesn't just
mean "publishing bills online" as is currently done &ndash; it means publishing
them online in such a way that each bit can be referred to and, more
importantly, contains the data necessary to join things up &ndash; e.g. when an
amendment paper says a particular amendment is going to change from halfway
through line 15 to line 18 of page 3, that amendment has its own ID,
and contains the means to point out what ID or IDs in the Bill are going to be
changed by this amendment. When a Public Bill committee votes on a particular
clause of a Bill, that reference is linked to the ID, so it can be
cross-referenced to what is being voted on. This would be of use not just to
the public, but to MPs, drafters, and everyone involved in the process.

<p>As an alternative, weaker, solution, you could bolt something on to the
current process, whereby just before the current bill or amendment text is
to be published on the web, it is passed through to a parser which tries to
extract as much structure as it can automatically, and then be passed on to
a human for checking and adding anything that it could not cope with.

<h4>The Data Schema</h4>

<p>We propose an initial structure for bills, amendments
and explanatory notes that has only a fistful of types, such as:</p>

<ul class="free_our_bill_reasons">
<li> Bill name </li>
<li> Title page rubrik </li>
<li> Date of publication </li>
<li> Name of MP who introduced the bill </li>
<li> Headings </li>
<li> Subheadings, sub-sub headings etc </li>
<li> Clauses, sub clauses, etc </li>
<li> Paragraphs, sub paragraphs etc </li>
<li> Names of other bills or acts and their paragraph and clause numbers. </li>
</ul>

<p>We are aware that some people might attempt to use this spec to claim
that mySociety doesn't understand the complex nature of bills. They
will try to pull out cases where this specification wouldn't work, and
use these as an excuse to say that what's really needed is a mega
project that will solve all problems at once, contain no mistakes, and
deliver in One Day. These people misunderstand the nature of
specifications in the Internet era.</p>

<p>What is important about this, or indeed any specification is not that
it is definitely right now and for the rest of eternity, what counts
is that it is revised and improved whenever it is discovered to
contain problems or mistakes. The key word above is initial: the spec
should evolve as new bills that don't quite fit are thrown at it. As
long as the spec is evolved in a sensible, open way, and published for
all to read and use, it will do its job admirably, and do it in a
hundredth the time of the 'ocean boiling' approach.</p>


<h3><a name="4"></a>What things could any programmer do if these changes were made?</h3>

<p>Here are just a few things we&rsquo;ve thought of &ndash; but it is the <em>structured</em>
nature of the data that is important, not what uses the data could possibly be put to.

<ul class="free_our_bill_reasons">
<li>You can&rsquo;t get an <strong>email alert</strong> to tell you when a bill mentions
something you might be interested in.
<li>You can&rsquo;t find out what <strong>amendments your own MP</strong> is asking for, or voting on.
<li>You can&rsquo;t learn, or help other people learn, about the process by <strong>annotating them</strong> to explain
what they&rsquo;re really going on about for everyone else.
<li>MPs and their staff can&rsquo;t receive services that would help them notice
when they were being asked to vote on dumb or <strong>dubious things</strong>.
<!-- <li>You can&rsquo;t get a <strong>rounded view</strong> of how useful your MP is if you
can&rsquo;t see their involvement with the bill making process. -->
<li>And about <strong>12 zillion</strong> other things that we&rsquo;re not even bright
enough to think of yet.
</ul>

<p>Here is something that was thrown together quite quickly. It's nothing more than the start of
the beginning of an idea of a
<a href="rough-demo/">rough-sketch demo</a>, and in no
way should any of its content be taken as what we would like Parliament to do, only the possibility
of what could be done with more structured data.</p>

<h3>We need you!</h3>

<?php signup_form() ?>

<p><a href="/freeourbills">Free our Bills home</a>

<?php
#$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end ();
