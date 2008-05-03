<?php

include_once "../../includes/easyparliament/init.php";

$this_page = 'wales_home';

$PAGE->page_start();
$PAGE->stripe_start();
?>
<h3>We need you!</h3>

<p>It'd be fantastic if TheyWorkForYou also covered the
Welsh Assembly, as we do with the
<a href="/ni/">Northern Ireland Assembly</a> and the
<a href="/scotland/">Scottish Parliament</a>, but we don't
currently have the time or resources ourselves &mdash; in fact,
both those assemblies were mainly done by volunteers.</p>

<p>If you're interested in volunteering to help out, please get
in touch!</p>

<p>Visit <a href="http://www.assemblywales.org/">the official Welsh Assembly website</a>.</p>

<?
$PAGE->stripe_end();
$PAGE->page_end();
