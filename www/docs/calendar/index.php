<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/recess.php";

$date = get_http_var('d');
if (!$date || !preg_match('#^\d\d\d\d-\d\d-\d\d$#', $date)) {
    calendar_summary();
} elseif ($date == date('Y-m-d')) {
    $this_page = 'calendar_today';
    calendar_date($date);
} elseif ($date > date('Y-m-d')) {
    $this_page = 'calendar_future';
    calendar_date($date);
} else {
    $this_page = 'calendar_past';
    calendar_date($date);
}

# ---

# Show upcoming stuff, perhaps a week or so, and links to view more.
# Sidebar of month calendar showing soonest future stuff
function calendar_summary() {
    global $PAGE, $this_page;

    $min_future_date = MySociety\TheyWorkForYou\Utility\Calendar::minFutureDate();
    if (!$min_future_date) {
        $this_page = 'calendar_future';
        $PAGE->error_message('We don&rsquo;t currently have any future information.
Why not explore our extensive archive using the search box above?');
        $PAGE->page_end();

        return;
    }

    if ($min_future_date == date('Y-m-d')) {
        $this_page = 'calendar_today';
    } else {
        $this_page = 'calendar_future';
    }

    return calendar_date($min_future_date);
}

function calendar_date($date) {
    global $DATA, $this_page, $hansardmajors;

    $DATA->set_page_metadata($this_page, 'title', format_date($date, LONGERDATEFORMAT));

    $db = new ParlDB();

    $data = array(
        'date' => $date,
    );
    $data['dates'] = MySociety\TheyWorkForYou\Utility\Calendar::fetchDate($date);

    $majors = array();
    if ($this_page == 'calendar_past') {
        $q = $db->query('SELECT DISTINCT major FROM hansard WHERE hdate = :date', array(
            ':date' => $date
            ));
        foreach ($q as $row) {
            $majors[] = $row['major'];
        }
    }

    $data['order'] = array(
        ['name'=>'Commons: Main Chamber', 'major'=>1, 'list'=>1],
        ['name'=>'Lords: Main Chamber', 'major'=>101, 'list'=>1],
        ['name'=>'Commons: Westminster Hall', 'major'=>2, 'list'=>1],
        ['name'=>'Commons: General Committee', 'major'=>6, 'list'=>1],
        ['name'=>'Commons: Select Committee', 'list'=>0],
        ['name'=>'Lords: Select Committee', 'list'=>0],
        ['name'=>'Lords: Grand Committee', 'list'=>1],
        ['name'=>'Joint Committee', 'list'=>1],
    );
    foreach ($data['order'] as &$chamber) {
        if (in_array($chamber['major'] ?? 0, $majors)) {
            $URL = new \MySociety\TheyWorkForYou\Url($hansardmajors[$chamber['major']]['page_all']);
            $URL->insert( array( 'd' => $date ) );
            $chamber['url'] = $URL->generate();
        }
    }

    $parent_page = $DATA->page_metadata($this_page, 'parent');

    $data['title'] = $DATA->page_metadata($this_page, 'title');
    $data['parent_title'] = $DATA->page_metadata($parent_page, 'title');

    $data = sidebar_calendars($data);

    $template = 'calendar/index';
    MySociety\TheyWorkForYou\Renderer::output($template, $data);
}

function sidebar_calendars($data) {
    $db = new ParlDB;

    $q = $db->query('SELECT MIN(event_date) AS min, MAX(event_date) AS max FROM future WHERE event_date >= NOW() AND deleted = 0')->first();
    $min_future_date = $q['min'];
    $max_future_date = $q['max'];
    if (!$min_future_date || !$max_future_date) {
        return;
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

    $years = array();
    if ($q->rows() > 0) {
        foreach ($q as $row) {
            list($year, $month, $day) = explode('-', $row['event_date']);
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
    }
    $data['years'] = $years;

    # Extra things calendar include needs
    $data['info'] = [
        'onday' => $data['date'],
    ];
    $data['recess_major'] = 1; # good enough
    $data['section'] = 'calendar';
    $data['urls'] = [
        'calendarday' => new \MySociety\TheyWorkForYou\Url('calendar_future'),
    ];

    return $data;
}
