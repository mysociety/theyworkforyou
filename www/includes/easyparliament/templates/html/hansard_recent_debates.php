<?php
// We're within $DEBATESLIST->render().

/*
    $data = array (
        'info' => '',
        'data' => array (
            array (
                'contentcount'     => 128,
                'body'            => 'My big bill',
                'hdate'            => '2004-03-24',
                'list_url'        => '/debates/?id=2004-03-24.342.234',
                'totalcomments'    => 2,
                'parent'    => array (
                    'body'        => 'My new clause 23'
                )
                'child' => ...
            ),
            etc.
        )
    );

    The 'parent' element is optional.

*/

twfy_debug("TEMPLATE", "hansard_biggest_debates.php");
if (array_key_exists('data', $data) && is_array($data['data'])) {
?>
                <dl class="big-debates">
<?php

$count = 0;

foreach ($data['data'] as $debate) {

    $count++;

    $extrainfo = array();

    $plural = $debate['contentcount'] == 1 ? 'speech' : 'speeches';
    $extrainfo[] = $debate['contentcount'] . ' ' . $plural;

    if ($debate['totalcomments'] > 0) {
        $plural = $debate['totalcomments'] == 1 ? 'annotation' : 'annotations';
        $extrainfo[] = $debate['totalcomments'] . ' ' . $plural;
    }

    $debateline = '<a href="' . $debate['list_url'] . '">';
    if ($debate['parent']['body']) {
        $debateline .= $debate['parent']['body'] . ': ';
    }
    $debateline .= $debate['body'] . '</a> <small>'
        . format_date($debate['hdate'], LONGERDATEFORMAT)
        . '; ' . implode(', ', $extrainfo)
        . '</small>';
?>
                <dt><?php echo $debateline; ?></dt>
                <dd><?=trim_characters($debate['child']['body'], 0, 200); ?></dd>
<?php
}
?>
                </dl>
<?php

}
