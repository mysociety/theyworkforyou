<?php

$this_page = "parliament_landing";

include_once '../../includes/easyparliament/init.php';

$DATA->set_page_metadata($this_page, 'title', 'Parliament');
$DATA->set_page_metadata($this_page, 'meta_description',
             'What goes on in Parliament? Up-to-the-minute records on all MPs, debates, speeches and statements from the House of Commons, House of Lords, Scottish Parliament, and Northern Ireland Assembly.');
$DATA->set_page_metadata($this_page, 'meta_keywords', 'parliament, uk parliament, parliamentary, house of commons, house commons, house of lords, house lords, house of parliament, parliment, houses parliament, parliament uk, member of parliament, welsh assembly, scottish parliament, the parliament, house of parliment, houses of parliment, parliment uk, uk parliment, houses of parliament, parliament houses, parliament of uk, parliament in uk, the house of parliament, scottish parliment, members of parliament, parliament members, scotish parliament, parliament scottish, the house of commons, british parliament, what is parliament, the house of lords, the scottish parliament, london parliament, parliament london, the houses of parliament, english parliament, northern ireland parliament, the british parliament, northern ireland assembly, history of parliament, parliament history, parliament of england, england parliament');

$PAGE->supress_heading = true;
$PAGE->page_start();
$PAGE->stripe_start('full');
?>

<div id="parliament_landing_banner" class="landing_banner">
  <div>
    <div class="transparent_white">
      <h1>Parliament
        <br><span>Search for any word or phrase &ndash; see if it&rsquo;s been mentioned in Parliament</span></h1>

<?php
$PAGE->search_form();
?>
    </div>
    <div class='clearboth'></div>
  </div>
  <div class='image_attribution'>Background image from <a href='http://www.flickr.com/photos/g4egk/4121065454/'>Greg Knapp</a></div>
</div>

<?php
$PAGE->stripe_end();
$PAGE->stripe_start();
?>

<p>In the UK, Parliament consists of the House of Commons and the House of Lords, with the monarch at the head.</p>

<p> The House of Commons is made up of democratically elected <a href='/mps/'>Members of Parliament (MPs)</a> representing every UK constituency. The <a href='/peers/'>House of Lords</a> is composed of unelected peers and senior bishops. Proceedings are overseen by a Speaker, whose job it is to retain order.</p>

<p>Parliament meets at the Houses of Westminster in London. Here there is a regular schedule of events, including <a href='/debates/'>debates</a>, <a href='/pbc/'>committees</a> and <a href='/written-answers-and-statements/'>parliamentary questions</a>. If you would like to learn more about what exactly goes on in Parliament, visit our <a href='/'>homepage</a>, where you can find out who your MP is, see recent debates and statements, and set up email alerts.</p>

<?php
$PAGE->stripe_end();
$PAGE->page_end();
?>
