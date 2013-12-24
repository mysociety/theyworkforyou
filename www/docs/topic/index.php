<?php

/*
 * List all static topics.
 */

include_once "../../includes/easyparliament/init.php";

$DATA->set_page_metadata($this_page, 'title', 'Topics');

$NEWPAGE->page_start();
$NEWPAGE->stripe_start('full');

?>

<p>TheyWorkForYou brings together information from a lot of different places,
and can be hard to get started with or find what you're looking for. Topics
bring together information about a specific subject.</p>

<ul>
    <li><a href="http://<?= DOMAIN ?>/topic/crime-stats">Crime Statistics</a></li>
</ul>

<?php

$NEWPAGE->stripe_end();
$NEWPAGE->page_end();
