<?php

global $PAGE, $hansardmajors;
$PAGE->page_start();
$PAGE->stripe_start();

$order = array(
    'Commons: Main Chamber', 'Lords: Main Chamber',
    'Commons: Westminster Hall',
    'Commons: General Committees',
    'Commons: Select Committees', 'Lords: Select Committees',
    'Lords: Grand Committees',
    'Joint Committees',
);
$list   = array(1, 1, 1, 1, 0, 0, 1, 1);
$major  = array(1, 101, 2, 6, 0, 0, 0, 0);

# Content goes here
foreach ($data['dates'] as $date => $day_events) {
    foreach ($order as $i => $chamber) {
        if (!array_key_exists($chamber, $day_events))
            continue;
        $events = $day_events[$chamber];
        print "<h2>$chamber";
        if (in_array($major[$i], $data['majors'])) {
            $URL = new \MySociety\TheyWorkForYou\Url($hansardmajors[$major[$i]]['page_all']);
            $URL->insert( array( 'd' => $date ) );
            print ' &nbsp; <a href="' . $URL->generate() . '">See this day &rarr;</a>';
        }
        print "</h2>\n";
        print $list[$i] ? "<ul class='future'>\n" : "<dl class='future'>\n";
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
