<?php

$notevery = true;

include_once '../../includes/easyparliament/init.php';
#require_once "sharethis.php";

// Share page for non-JS browsers

$this_page = 'campaign';
$PAGE->page_start();
$PAGE->stripe_start();

    ?>
        <h2>All done! Your message is on its way now. Thank you.</h2>
        <p></p>
    <?php

$PAGE->block_start(array ('title'=>'Share this with your friends'));

#foi2009_sharethis_link();
#foi2009_share_page();

?>		<p><a href="/">Return to TheyWorkForYou homepage</a> <?php

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end ();
