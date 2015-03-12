<?php
// We're within $WRANSLIST->render().

/*
    $data = array (
        'info' => '',
        'data' => array (
            array (
                'body'			=> 'Fisheries',
                'hdate'			=> '2004-03-24',
                'list_url'		=> '/wrans/?id=2004-03-24.342W.234',
                'totalcomments'	=> 2,
                'child'	=> array (
                    'body'		=> '<p>To ask the Secretary of State ... </p>'
                ),
                'parent'	=> array (
                    'body'		=> 'Environment'
                )
            ),
            etc.
        )
    );


*/

twfy_debug("TEMPLATE", "hansard_recent_wrans.php");

echo '<dl class="recent-wrans">';

$count = 0;

foreach ($data['data'] as $wran) {

    $count++;

    $extrainfo = array();

    if ($wran['totalcomments'] > 0) {
        $plural = make_plural('annotation', $wran['totalcomments']);
        $totalcomments = '; ' . $wran['totalcomments'] . ' ' . $plural;
    } else {
        $totalcomments = '';
    }

    $speaker = $wran['child']['speaker'];
    echo '<dt><a name="w', $count, '"></a><strong><a href="', $wran['list_url'], '">';
    if ($wran['parent']['body']) echo $wran['parent']['body'], ': ';
    echo $wran['body'], '</a></strong> <small>answered ', \MySociety\TheyWorkForYou\Utility\DateTime::formatDate($wran['hdate'], LONGDATEFORMAT),
        $totalcomments, '</small></dt><dd>';
    if (sizeof($speaker)) {
        $body = preg_replace('/<p[^>]*>/', '', $wran['child']['body'], 1);
        echo '<p><a href="', $speaker['url'], '">', member_full_name($speaker['house'], $speaker['title'], $speaker['first_name'], $speaker['last_name'], $speaker['constituency']), '</a>: ';
    } else {
        $body = $wran['child']['body'];
    }
    echo $body;
    echo '</dd>';
}

echo '</dl>';
