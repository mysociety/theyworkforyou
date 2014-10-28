<?php

global $PAGE, $hansardmajors;
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
$major  = array(1, 101, 2, 0, 0, 0, 0);

# Content goes here
foreach ($data['dates'] as $date => $day_events) {
    foreach ($order as $i => $chamber) {
        if (!array_key_exists($chamber, $day_events))
            continue;
        $events = $day_events[$chamber];
        if ($plural[$i]) $chamber .= 's';
        print "<h2 class='calendar'>$chamber";
        if (in_array($major[$i], $data['majors'])) {
            $URL = new \MySociety\TheyWorkForYou\Url($hansardmajors[$major[$i]]['page_all']);
            $URL->insert( array( 'd' => $date ) );
            print ' &nbsp; <a href="' . $URL->generate() . '">See this day &rarr;</a>';
        }
        print "</h2>\n";
        print $list[$i] ? "<ul class='calendar'>\n" : "<dl class='calendar'>\n";
        foreach ($events as $event) {
            \MySociety\TheyWorkForYou\Utility\Calendar::displayEntry($event);
        }
        print $list[$i] ? "</ul>\n" : "</dl>\n";
    }
}

$PAGE->stripe_end(array(
    array(
        'type' => 'include',
        'content' => 'calendar_box'
    ),
    array(
        'type' => 'html',
        'content' => '
<div class="block">
<h4>Search upcoming business, or set up a future business email alert</h4>
<div class="blockbody">
<form action="/search/" method="get">
<p><input type="text" name="s" value="" size="40"> <input type="submit" value="Go">
<input type="hidden" name="section" value="future">
</p>
</form>
</div>
</div>
        '
    ),
    array(
        'type' => 'include',
        'content' => 'calendar_future'
    ),
));

$PAGE->page_end();
