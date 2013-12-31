<?php

/*
 * List all static topics.
 */

include_once "../../includes/easyparliament/init.php";

$DATA->set_page_metadata($this_page, 'title', 'Topics');

$NEWPAGE->page_start();
$NEWPAGE->stripe_start('full');

// Array of topic page names (must exist in metadata.php) and titles to display.
$topics = array(
    'topiccrimestats' => 'Crime Statistics',
    'nhs'             => 'NHS'
);

?>

<p>TheyWorkForYou brings together information from a lot of different places,
and can be hard to get started with or find what you're looking for. Topics
bring together information about a specific subject.</p>

<ul>

<?php foreach ($topics as $page => $topic): ?>

    <?php $URL = new URL($page); ?>

    <li><a href="http://<?= DOMAIN ?><?= $URL->generate(); ?>"><?= htmlspecialchars($topic); ?></a></li>

<?php endforeach; ?>

</ul>

<?php

$NEWPAGE->stripe_end();
$NEWPAGE->page_end();
