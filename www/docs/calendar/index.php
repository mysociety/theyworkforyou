<?php

include_once "../../includes/easyparliament/init.php";

$date = get_http_var('d');
if (!$date || !preg_match('#^\d\d\d\d-\d\d-\d\d$#', $date)) {
    $this_page = 'calendar_future';
    calendar_summary();
} elseif ($date >= date('Y-m-d')) {
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
    global $PAGE;

    $db = new ParlDB();
    $q = $db->query('SELECT MIN(event_date) AS m FROM future WHERE event_date >= NOW()');
    $min_future_date = $q->field(0, 'm');
    if (!$min_future_date) {
        $PAGE->error_message('There is no future information in the database currently.');
        $PAGE->page_end();
        return;
    }

    return calendar_date($min_future_date);
}

function calendar_date($date) {
    global $this_page, $DATA, $PAGE;

    $db = new ParlDB();

    $q = $db->query("SELECT * FROM future
        LEFT JOIN future_people ON future.id = future_people.calendar_id AND witness = 0
        WHERE event_date = '$date'
        AND deleted = 0
        ORDER BY chamber");

    if (!$q->rows()) {
        $PAGE->error_message('There is currently no information available for that date.');
        $PAGE->page_end();
        return;
    }

    $DATA->set_page_metadata($this_page, 'date', $date);

    $data = array();
    foreach ($q->data as $row) {
        $data['dates'][$row['event_date']][$row['chamber']][] = $row;
    }

    $data['majors'] = array();
    if ($this_page == 'calendar_past') {
        $q = $db->query('SELECT DISTINCT major FROM hansard WHERE hdate = "' . mysql_escape_string($date) . '"');
        foreach ($q->data as $row) {
            $data['majors'][] = $row['major'];
        }
    }

    include_once INCLUDESPATH . 'easyparliament/templates/html/calendar_date.php';
}

function calendar_display_entry($e) {
    $private = false;
    if ($e['committee_name']) {
        $title = $e['committee_name'];
        if ($e['title'] == 'to consider the Bill') {
        } elseif ($e['title'] && $e['title'] != 'This is a private meeting.') {
            $title .= ': ' . $e['title'];
        } else {
            $private = true;
        }
    } else {
        $title = $e['title'];
        if ($pid = $e['person_id']) {
            $MEMBER = new MEMBER(array( 'person_id' => $pid ));
            $name = $MEMBER->full_name();
            $title .= " &ndash; <a href='/mp/?p=$pid'>$name</a>";
        }
    }

    $meta = array();

    if ($e['debate_type'] == "Prime Minister's Question Time") {
        $title = $e['debate_type'];
    } elseif ($d = $e['debate_type']) {
        if ($d == 'Adjournment') $d = 'Adjournment debate';
        $meta[] = $d;
    }

    if ($e['time_start'] || $e['location']) {
        if ($e['time_start']) {
            $time = format_time($e['time_start'], TIMEFORMAT);
            if ($e['time_end'])
                $time .= ' &ndash; ' . format_time($e['time_end'], TIMEFORMAT);
            $meta[] = $time;
        }
        if ($e['location'])
            $meta[] = $e['location'];
    }
    if ($private)
        $meta[] = 'Private meeting';

    if (strstr($e['chamber'], 'Select Committee')) {
        print '<dt class="sc">';
    } else {
        print '<li>';
    }
    print "$title ";
    if ($meta) print '<span>' . join('; ', $meta) . '</span>';
    if (strstr($e['chamber'], 'Select Committee')) {
        print "</dt>\n";
    } else {
        print "</li>\n";
    }
    if ($e['witnesses']) {
        print "<dd>";
        print '<a href=" $e[link_calendar] "></a>';
        print '<a href=" $e[link_external] "></a>';
        print 'Witnesses: ' . $e['witnesses'];
        print "</dd>\n";
    }
}

# ---

/*
function calendar_past_date($date) {
    global $PAGE, $DATA, $this_page, $hansardmajors;

	$PAGE->set_hansard_headings(array('date'=>$date));
	$URL = new URL($this_page);
	$nextprevdata = array();
	$db = new ParlDB;
	$q = $db->query("SELECT MIN(hdate) AS hdate FROM hansard WHERE hdate > '$date'");
	if ($q->rows() > 0 && $q->field(0, 'hdate') != NULL) {
		$URL->insert( array( 'd'=>$q->field(0, 'hdate') ) );
		$title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
		$nextprevdata['next'] = array (
			'hdate'         => $q->field(0, 'hdate'),
			'url'           => $URL->generate(),
			'body'          => 'Next day',
			'title'         => $title
		);
	}
	$q = $db->query("SELECT MAX(hdate) AS hdate FROM hansard WHERE hdate < '$date'");
	if ($q->rows() > 0 && $q->field(0, 'hdate') != NULL) {
		$URL->insert( array( 'd'=>$q->field(0, 'hdate') ) );
		$title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
		$nextprevdata['prev'] = array (
			'hdate'         => $q->field(0, 'hdate'),
			'url'           => $URL->generate(),
			'body'          => 'Previous day',
			'title'         => $title
		);
	}
	#	$year = substr($date, 0, 4);
	#	$URL = new URL($hansardmajors[1]['page_year']);
	#	$URL->insert(array('y'=>$year));
	#	$nextprevdata['up'] = array (
		#		'body'  => "All of $year",
		#		'title' => '',
		#		'url'   => $URL->generate()
		#	);
	$DATA->set_page_metadata($this_page, 'nextprev', $nextprevdata);
	$PAGE->page_start();
	$PAGE->stripe_start();
	include_once INCLUDESPATH . 'easyparliament/recess.php';
	$time = strtotime($date);
	$dayofweek = date('w', $time);
	$recess = recess_prettify(date('j', $time), date('n', $time), date('Y', $time), 1);
	if ($recess[0]) {
		print '<p>The Houses of Parliament are in their ' . $recess[0] . ' at this time.</p>';
	} elseif ($dayofweek == 0 || $dayofweek == 6) {
		print '<p>The Houses of Parliament do not meet at weekends.</p>';
	} else {
		$data = array(
			'date' => $date
		);
		foreach (array_keys($hansardmajors) as $major) {
			$URL = new URL($hansardmajors[$major]['page_all']);
			$URL->insert(array('d'=>$date));
			$data[$major] = array('listurl'=>$URL->generate());
		}
		major_summary($data);
	}
	$PAGE->stripe_end(array(
		array (
			'type' 	=> 'nextprev'
		),
	));
	$PAGE->page_end();
}
*/

