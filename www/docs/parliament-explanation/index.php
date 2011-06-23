<?php

$this_page = "landing";

include_once "../../includes/easyparliament/init.php";

$DATA->set_page_metadata($this_page, 'title', 'Parliament');
$DATA->set_page_metadata($this_page, 'metadescription', 
			 'What goes on in Parliament? Up-to-the-minute records on all MPs, debates, speeches and statements from the House of Commons, House of Lords, Scottish Parliament, Northern Ireland and Welsh assemblies.');
$DATA->set_page_metadata($this_page, 'metakeywords', 'parliament, uk parliament, parliamentary, house of commons, house commons, house of lords, house lords, house of parliament, parliment, houses parliament, parliament uk, member of parliament, welsh assembly, scottish parliament, the parliament, house of parliment, houses of parliment, parliment uk, uk parliment, houses of parliament, parliament houses, parliament of uk, parliament in uk, the house of parliament, scottish parliment, members of parliament, parliament members, scotish parliament, parliament scottish, the house of commons, british parliament, what is parliament, the house of lords, the scottish parliament, london parliament, parliament london, the houses of parliament, english parliament, northern ireland parliament, the british parliament, northern ireland assembly, history of parliament, parliament history, parliament of england, england parliament');

$PAGE->supress_heading = true;
$PAGE->page_start();
$PAGE->stripe_start('full');
?>

<div id="parliament_landing_banner" class="landing_banner">
  <div>
    <div class="transparent_white">
      <h2>Parliament</h2>
  <h3>Search for any word or phrase &ndash; see if it&rsquo;s been mentioned in Parliament</h3>

<?php
$PAGE->search_form();
?>

    </div>
  </div>
</div>

<?php
$PAGE->stripe_end();
$PAGE->stripe_start();
?>

<p>TheyWorkForYou.com is an independent website containing complete records of everything that goes on in Parliament. Use the search box above to see how Members of Parliament talk about the topics that affect you, like daylight saving, minimum wage, or the NHS.<p>

<?php
$PAGE->stripe_end();
$PAGE->page_end();
?>
