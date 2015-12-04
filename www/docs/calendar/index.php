<?php

$new_style_template = TRUE;

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

global $hansardmajors;

$min_future_date = MySociety\TheyWorkForYou\Utility\Calendar::minFutureDate();

$data = array();
$data['date'] = get_http_var('d');
$data['majors'] = array();

// If no date is specified in the URL, fall back to the nearest future date,
// or remove the date entirely, so the template can render a generic message.
if (!$data['date'] || !preg_match('#^\d\d\d\d-\d\d-\d\d$#', $data['date'])) {
    if ($min_future_date) {
        $data['date'] = $min_future_date;
    } else {
        unset($data['date']);
        $data['scope'] = 'calendar_summary';
    }
}

if($data['date']){
    $data['dates'] = MySociety\TheyWorkForYou\Utility\Calendar::fetchDate($data['date']);

    if ($data['date'] == date('Y-m-d')) {
        $data['scope'] = 'calendar_today';
    } elseif ($data['date'] > date('Y-m-d')) {
        $data['scope'] = 'calendar_future';
    } else {
        $data['scope'] = 'calendar_past';
        $db = new ParlDB();
        $q = $db->query('SELECT DISTINCT major FROM hansard WHERE hdate = :date', array(
            ':date' => $data['date']
        ));
        foreach ($q->data as $row) {
            $data['majors'][] = $row['major'];
        }
    }
}

// TODO: We need to set the page title, and make sure the site navigation is displayed

MySociety\TheyWorkForYou\Renderer::output('calendar/index', $data);