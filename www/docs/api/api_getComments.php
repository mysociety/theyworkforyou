<?php

function api_getComments_front() {
?>
<p><big>Fetch comments left on TheyWorkForYou.</big></p>

<p>With no arguments, returns most recent comments in reverse date order.</p>

<h4>Arguments</h4>
<dl>
<dt>start_date, end_date (optional)</dt>
<dd>Fetch the comments between two dates (inclusive).</dd>
<dt>search (optional)</dt>
<dd>Fetch the comments that contain this term.</dd>
<dt>pid</dt>
<dd>Fetch the comments made on a particular person ID (MP/Lord).</dd>
<dt>page (optional)</dt>
<dd>Page of results to return.</dd>
<dt>num (optional)</dt>
<dd>Number of results to return.</dd>
</dl>

<h4>Example Response</h4>

<?php
}

function api_getComments_start_date($start_date) {
        $args = array (
            'start_date' => $start_date,
            'end_date' => get_http_var('end_date')
        );
    $commentlist = new \MySociety\TheyWorkForYou\CommentList($PAGE, $hansardmajors);
    $commentlist->display('dates', $args, 'api');
}

function api_getComments_search($s) {
        $args = array (
            's' => $s,
            'p' => get_http_var('page'),
            'num' => get_http_var('num'),
        );
    $commentlist = new \MySociety\TheyWorkForYou\CommentList($PAGE, $hansardmajors);
    $commentlist->display('search', $args, 'api');
}

function api_getComments() {
    $args = array(
        'page' => get_http_var('p'),
            'num' => get_http_var('num'),
    );
    $COMMENTLIST = new \MySociety\TheyWorkForYou\CommentList($PAGE, $hansardmajors);
    $COMMENTLIST->display('recent', $args, 'api');
}

function api_getComments_pid($pid) {
    $args = array(
        'page' => get_http_var('p'),
            'num' => get_http_var('num'),
        'pid' => $pid
    );
    $COMMENTLIST = new \MySociety\TheyWorkForYou\CommentList($PAGE, $hansardmajors);
    $COMMENTLIST->display('recent', $args, 'api');
}

?>
