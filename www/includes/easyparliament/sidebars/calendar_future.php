<?php

global $PAGE, $DATA, $this_page;

// The calendar that appears in sidebars linking to future calendar dates

$date = $DATA->page_metadata($this_page, 'date');

$PAGE->block_start(array('title' => 'Future business calendar', 'id'=>'calendar'));

$data = array(
    'info' => array(
        'page' => 'calendar_future',
        'major' => 1, # For recess dates only - good enough
        'onday' => $date,
        'all' => 1,
    )
);

$action = 'all';

$db = new \MySociety\TheyWorkForYou\ParlDb;

$q = $db->query('SELECT MIN(event_date) AS min, MAX(event_date) AS max FROM future WHERE event_date >= NOW() AND deleted = 0');
$min_future_date = $q->field(0, 'min');
$max_future_date = $q->field(0, 'max');
if (!$min_future_date || !$max_future_date) {
    $PAGE->error_message("Couldn't find any future information");

    return $data;
}

list($firstyear, $firstmonth, $day) = explode('-', $min_future_date);
list($finalyear, $finalmonth, $day) = explode('-', $max_future_date);

$q =  $db->query("SELECT DISTINCT(event_date) AS event_date FROM future
    WHERE event_date >= :firstdate
    AND event_date <= :finaldate
    AND deleted = 0
    ORDER BY event_date ASC
", array(
    ':firstdate' => $firstyear . '-' . $firstmonth . '-01',
    ':finaldate' => $finalyear . '-' . $finalmonth . '-31'
));

if ($q->rows() > 0) {
    $years = array();
    for ($row=0; $row<$q->rows(); $row++) {
        list($year, $month, $day) = explode('-', $q->field($row, 'event_date'));
        $month = intval($month);
        $years[$year][$month][] = intval($day);
    }

    // If nothing happened on one month we'll have fetched nothing for it.
    // So now we need to fill in any gaps with blank months.

    // We cycle through every year and month we're supposed to have fetched.
    // If it doesn't have an array in $years, we create an empty one for that
    // month.
    for ($y = intval($firstyear); $y <= $finalyear; $y++) {

        if (!isset($years[$y])) {
            $years[$y] = array(1=>array(), 2=>array(), 3=>array(), 4=>array(), 5=>array(), 6=>array(), 7=>array(), 8=>array(), 9=>array(), 10=>array(), 11=>array(), 12=>array());
        } else {
            // This year is set. Check it has all the months...
            $minmonth = $y == $firstyear ? $firstmonth : 1;
            $maxmonth = $y == $finalyear ? $finalmonth : 12;
            for ($m = intval($minmonth); $m <= $maxmonth; $m++) {
                if (!isset($years[$y][$m])) {
                    $years[$y][$m] = array();
                }
            }
            ksort($years[$y]);
        }
    }

    $data['years'] = $years;
}

include INCLUDESPATH . 'easyparliament/templates/html/hansard_calendar.php';

$PAGE->block_end();
