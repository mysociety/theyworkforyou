<?php

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH."easyparliament/people.php";

if (get_http_var('msp')) {
    list_reps('msps', 'MSPs', 'msp_search');
} elseif (get_http_var('mla')) {
    list_reps('mlas', 'MLAs', 'mla_search');
} elseif (get_http_var('peer')) {
    list_reps('peers', 'Members of the House of Lords', 'peer_search');
} else {
    list_reps('mps', 'MPs', 'mp_search');
}

function list_reps($type, $rep_plural, $search_sidebar) {
    global $this_page, $PAGE, $DATA;

    $this_page = $type;

    $args = array();
    if ($type == 'peers')
        $args['order'] = 'name';

    $date = get_http_var('date');
    if ($date) {
        $date = parse_date($date);
        if ($date) {
            $DATA->set_page_metadata($this_page, 'title', $rep_plural . ', as on ' . format_date($date['iso'], LONGDATEFORMAT));
            $args['date'] = $date['iso'];
        }
    } elseif (get_http_var('all')) {
        $DATA->set_page_metadata($this_page, 'title', 'All ' . $rep_plural . ', including former ones');
        $args['all'] = true;
    } else {
        $DATA->set_page_metadata($this_page, 'title', 'All ' . $rep_plural);
    }

    if (get_http_var('f') != 'csv') {
        $PAGE->page_start();
        $PAGE->stripe_start();
        $format = 'html';
    } else {
        $format = 'csv';
    }

    $order = get_http_var('o');
    $orders = array(
        'n' => 'name', 'f' => 'first_name', 'l' => 'last_name',
        'c' => 'constituency', 'p' => 'party', 'e' => 'expenses',
        'd' => 'debates', 's' => 'safety'
    );
    if (array_key_exists($order, $orders))
        $args['order'] = $orders[$order];

    $PEOPLE = new PEOPLE;
    $PEOPLE->display($type, $args, $format);

    if (get_http_var('f') != 'csv') {
        $PAGE->stripe_end(array(
            array('type' => 'include', 'content' => 'minisurvey'),
            array('type' => 'include', 'content' => 'people'),
            array('type' => 'include', 'content' => $search_sidebar),
        ));
        $PAGE->page_end();
    }

}
