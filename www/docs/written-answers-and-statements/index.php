<?php
#
# Index page for written answers/statements.

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/member.php";

$this_page = 'wranswmsfront';
$PAGE->page_start();

# XXX Search box currently in the stripe below should, when wrans and wms are
# two columns, be moved up here spanning across the two columns.

echo '<div id="written-answers">';
$PAGE->stripe_start();
echo '<h2>Some recent written answers</h2>';
$WRANSLIST = new WRANSLIST;
$WRANSLIST->display('recent_wrans', array('days'=>7, 'num'=>5));
$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => 'minisurvey'
	),
	array(
		'type' => 'html',
		'content' => '
<div class="block">
<h4>Search written answers and statements</h4>
<div class="blockbody">
<form action="/search/" method="get">
<p><input type="text" name="s" value="" size="40"> <input type="submit" value="Go">
<br><input type="checkbox" name="section[]" value="wrans" checked id="section_wrans">
<label for="section_wrans">Written answers</label>
<input type="checkbox" name="section[]" value="wms" checked id="section_wms">
<label for="section_wms">Written ministerial statements</label>
</p>
</form>
</div>
</div>
',
	),
	array (
		'type' => 'nextprev'
	),
	array (
		'type' => 'include',
		'content' => 'calendar_wrans'
	),
		array (
		'type' => 'include',
		'content' => "wrans"
	)
));
echo '</div>';

echo '<div id="written-statements">';
$PAGE->stripe_start();
echo '<h2>Some recent written ministerial statements</h2>';
$WMSLIST = new WMSLIST;
$WMSLIST->display('recent_wms', array('days'=>7, 'num'=>20));
$rssurl = $DATA->page_metadata($this_page, 'rss');
$PAGE->stripe_end(array(
	array (
		'type' => 'nextprev'
	),
	array (
		'type' => 'include',
		'content' => 'calendar_wms'
	),
	array (
		'type' => 'include',
		'content' => "wms"
	),
	array (
		'type' => 'html',
		'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img border="0" alt="RSS feed" align="middle" src="http://www.theyworkforyou.com/images/rss.gif"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of recent statements</a></p>
</div>'

	)
));
echo '</div>';

$PAGE->page_end();
