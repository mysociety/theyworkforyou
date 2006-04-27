<?php
// This sidebar is on the search page.

$this->block_start(array(
	'id'	=> 'help',
	'title'	=> "Search Tips"
));
?>


<ul>

<li>TheyWorkForYou.com Search is case-insensitive, and tries to match all the search terms within a document. </li>

<li>To exclude a word from your search, put a minus ("-") sign in front,
for example to find documents containing the word "representation" but not the word "taxation":<br />
<span class="example-input">representation -taxation</span></li>

<li>To search for an exact phrase, use quotes (""). For example to find only documents contain the exact phrase "Hutton Report":<br />
<span class="example-input">"hutton report"</span></li>

<li>If the search phrase matches (part of) an MP or Peer's name, their own page will appear as the top result.</li>

<li>If your search term matches a glossary definition, a link to that definition will appear as the top result.</li>


<li>From an MP or Peer's page, you have the option to search only their speeches. </li>

</ul>

<?php
$this->block_end();
?>
