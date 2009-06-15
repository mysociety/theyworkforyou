<?php
#
# Index page for written answers/statements.

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/glossary.php";
include_once INCLUDESPATH . "easyparliament/member.php";

$this_page = 'wranswmsfront';
$PAGE->page_start();

?>

<form action="/search/" method="get">
<p align="center">Search these: <input type="text" name="s" value="" size="50">
<br><input type="checkbox" name="section[]" value="wrans" checked> Written answers
<input type="checkbox" name="section[]" value="wms" checked> Written ministerial statements
<input type="submit" value="Search">
</form>

<?

echo '<div id="written-answers>';
$PAGE->stripe_start();
echo '<h3>Some recent written answers</h3>';
$WRANSLIST = new WRANSLIST;
$WRANSLIST->display('recent_wrans', array('days'=>7, 'num'=>5));
$PAGE->stripe_end(array(
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
echo '<h3>Some recent written ministerial statements</h3>';
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

