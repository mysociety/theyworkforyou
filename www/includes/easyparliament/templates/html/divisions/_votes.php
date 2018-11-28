<?php

// Charts/lists in the other they should appear on the page.
$division_sections = array(
    array(
        'thekey' => 'yes_votes',
        'style' => 'dot_vote_list',
    ),
    array(
        'thekey' => 'no_votes',
        'style' => 'dot_vote_list',
    ),
    array(
        'thekey' => 'yes_votes',
        'style' => 'name_vote_list',
    ),
    array(
        'thekey' => 'no_votes',
        'style' => 'name_vote_list',
    ),
    array(
        'thekey' => 'absent_votes',
        'style' => 'dot_vote_list',
    ),
    array(
        'thekey' => 'both_votes',
        'style' => 'dot_vote_list',
    ),
    array(
        'thekey' => 'absent_votes',
        'style' => 'name_vote_list',
    ),
    array(
        'thekey' => 'both_votes',
        'style' => 'name_vote_list',
    ),
);

$vote_sets = array(
    'yes_votes' => array(
      'title' => 'Aye',
      'anchor' => 'for',
    ),
    'no_votes' => array(
      'title' => 'No',
      'anchor' => 'against',
    ),
    'absent_votes' => array(
      'title' => 'Absent',
      'anchor' => 'absent',
    ),
    'both_votes' => array(
      'title' => 'Abstained',
      'anchor' => 'both',
    ),
);

foreach ( $division_sections as $s ) {
    $vote_title = $vote_sets[ $s['thekey'] ]['title'];
    $anchor = $vote_sets[ $s['thekey'] ]['anchor'];
    $summary = $division['party_breakdown'][ $s['thekey'] ];

    if ( $s['style'] == 'dot_vote_list' ) {
        $votes = $division[ $s['thekey'] . '_by_party' ];
        include '_dot_vote_list.php';

    } else if ( $s['style'] == 'name_vote_list' ) {
        $votes = $division[ $s['thekey'] ];
        include '_name_vote_list.php';
    }
}
