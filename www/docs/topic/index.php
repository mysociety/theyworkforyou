<?php

/*
 * List all static topics.
 */

include_once "../../includes/easyparliament/init.php";

$NEWPAGE->page_start();
$NEWPAGE->stripe_start('full');

?>

<h1>Topics</h1>

<ul>
    <li><a href="http://<?= DOMAIN ?>/topic/crime-stats">Crime Statistics</a></li>
</ul>

<?php

$NEWPAGE->stripe_end();
$NEWPAGE->page_end();
