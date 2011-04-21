<?php

global $PAGE;
$PAGE->page_start();
$PAGE->stripe_start();

$order = array(
    'Commons: Main Chamber', 'Lords: Main Chamber',
    'Commons: Westminster Hall',
    'Commons: General Committee',
    'Commons: Select Committee', 'Lords: Select Committee',
    'Lords: Grand Committee',
);
$plural = array(0, 0, 0, 1, 1, 1, 0);
$list   = array(1, 1, 1, 1, 0, 0, 1);

# Content goes here
foreach ($data as $date => $day_events) {
    print "<h3>" . format_date($date, LONGERDATEFORMAT) . "</h3>\n";
    foreach ($order as $i => $chamber) {
        if (!array_key_exists($chamber, $day_events))
            continue;
        $events = $day_events[$chamber];
        if ($plural[$i]) $chamber .= 's';
        print "<h4>$chamber</h4>\n";
        print $list[$i] ? "<ul class='calendar'>\n" : "<dl class='calendar'>\n";
        foreach ($events as $event) {
            calendar_display_entry($event);
        }
        print $list[$i] ? "</ul>\n" : "</dl>\n";
    }
}

$PAGE->stripe_end(array(
    array (
        'type' => 'include',
        'content' => 'calendar_future'
    ),
));

$PAGE->page_end();

