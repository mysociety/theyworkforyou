<?php

$this_page = "privacy";

include_once "../../includes/easyparliament/init.php";

$PAGE->page_start();

$PAGE->stripe_start();

?>
<p>Our Privacy Policy is very simple:</p>

<ol>
<li>We guarantee we will not sell or distribute any personal information you share with us</li>
<li>We will not be sending you unsolicited email</li>
<li>We will gladly show you the personal data we store about you in order to run the website</li>
</ol>

<p><em>We hope you enjoy using the website.</em></p>
<?

$PAGE->stripe_end();

$PAGE->page_end();

?>
