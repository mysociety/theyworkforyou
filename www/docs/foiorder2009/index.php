<?php

$notevery = true;
include_once '../../includes/easyparliament/init.php';
include 'fns.php';
$this_page = 'campaign_foi';

$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(array ('id'=>'intro', 'title'=>'We need your help:'));

echo '<div id="foi2009">';
echo $foi2009_message;
echo '</div>';

$PAGE->block_end();
$PAGE->stripe_end();
$PAGE->page_end();
