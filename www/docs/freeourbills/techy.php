<?php
include_once "../../includes/easyparliament/init.php";
require_once "share.php";

$this_page = 'campaign';
$PAGE->page_start();
$PAGE->stripe_start();
#$PAGE->block_start(array ('id'=>'intro', 'title'=>'We need your help:'));

freeourbills_styles();

?>

<h2>Free our Bills!</h2>

<p id="free_our_bills_banner">The Nice Polite Campaign to Gently Encourage
Parliament to Publish Bills in a 21st Century Way, Please. Now.</p>

<p></p>

<h3>Details of the technical changes we want Parliament to make to the way
it publishes bills.</h3>

<h3>Background</h3>

<p>As bills are written they go through various stages. Towards the very
end of the process, they are dumped out in a format that is sent to
the printers, and which is converted to HTML so that it can be put up
on the Parliamentary website.</p>

<p>We don't want to change anything about this process at all - we don't
want to disturb the hard working people who have this important job.
We just want the following to happen:</p>

<p>1. At the stage where the completed bill text is finished and ready
for printing, an electronic copy of each bill or list of amendments
needs to be copied onto a new, external server (external so that this
entire project has no implications for network security). It should be
possible to do this using a one or two line script running on a
parliamentary server.</p>

<p>2. A different script on the external server attempts to parse the
bills to mark them up with a basic structure (see below for more
details)</p>

<p>3. A parliamentary official gets notified by email that there is a new
bill that needs checking. They click on the link in their email
client, which loads straight into a page containing the new bill.
Different sorts of structure are highlighted in different colours, and
their job is to look carefully through the document to see if the
parser has correctly identified every heading, subheading, paragraph,
bill name etc. If they find that the parser has made mistakes they
edit them directly in the browser using a WYSIWYG editor.</p>

<p>4. Once they are happy that the bill has been correctly marked up.
They hit 'save' and a copy is immediately published on the external
server, ready for third parties to re-use in whatever way they want.</p>

<p>In this whole process the text of the bill is never touched or
changed: there are no issues here about changing the meaning of bills.
Amendments and explanatory notes will be processed in a similar way.
</p>

<p>Once we've done that, mySociety (and anyone else) will be able to
build sites a bit like this rough-sketch demo, only much better.</p>

<h3>The Data Schema</h3>

<p>We propose an initial XML structure for parsing bills, amendments
and explanatory notes that has only a fistful of types, such as:</p>

<ul>
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

<h3>The Correction Software Specification</h3>

<p>The schema is no use on its own. It needs to be built hand in hand
with a parser and a browser based corrections interface.</p>

<h3>We need you!</h3>

<? signup_form() ?>

<p><a href="/freeourbills">To 'Free our Bills' homepage</a>

<?
#$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end ();



