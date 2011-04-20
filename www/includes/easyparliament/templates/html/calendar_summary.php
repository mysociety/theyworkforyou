<?php

global $PAGE;
$PAGE->page_start();
$PAGE->stripe_start();

# Content goes here

$PAGE->stripe_end(array(
    array (
        'type' => 'include',
        'content' => 'calendar_future'
    ),
));

$PAGE->page_end();

