<?php

// The user comes to this page after clicking an 'add a comment' link
// in hansard, if they aren't logged in.

// This page will expect a 'ret' value with the URL of the page
// the user should return to after logging in.


$this_page = "userprompt";

include_once '../../../includes/easyparliament/init.php';


$type = get_http_var('type');
$returl = get_http_var('ret');

$message = "Sorry, you must be logged in to add an annotation.";
$message2 = "You'll be able to post your annotation straight after.";
$anchor = '#addcomment';

$URL = new \MySociety\TheyWorkForYou\Url('userjoin');
$URL->insert(['ret' => $returl . $anchor]);
$joinurl = $URL->generate();


// GET THAT PAGE STARTED!

$PAGE->page_start();

$PAGE->stripe_start();

?>

<p><strong><?php echo $message; ?></strong></p>

<p>If you're not yet a member, then <a href="<?php echo $joinurl; ?>"><strong>join now</strong></a>.</p>

<p>Otherwise, please sign in... <?php echo $message2; ?></p>

<?php

$PAGE->login_form();

$PAGE->stripe_end();

$PAGE->page_end();

?>
