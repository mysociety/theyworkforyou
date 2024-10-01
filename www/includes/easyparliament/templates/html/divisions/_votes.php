<?php

$vote_sets = [
    'yes_votes' => [
        'title' => gettext('Aye'),
        'anchor' => 'for',
    ],
    'no_votes' => [
        'title' => gettext('No'),
        'anchor' => 'against',
    ],
    'absent_votes' => [
        'title' => gettext('Absent'),
        'anchor' => 'absent',
    ],
    'both_votes' => [
        'title' => gettext('Abstained'),
        'anchor' => 'both',
    ],
];

$sections_with_votes = array_filter(array_keys($vote_sets), function ($s) use ($division) {
    return count($division[$s]);
});
$sections_with_votes = array_values($sections_with_votes);

for ($i = 0; $i < count($sections_with_votes); $i += 2) {
    $l = $sections_with_votes[$i];
    $r = $i + 1 < count($sections_with_votes) ? $sections_with_votes[$i + 1] : null;

    $vote_title = $vote_sets[$l]['title'];
    $anchor = $vote_sets[$l]['anchor'];
    $summary = $division['party_breakdown'][$l];
    $votes = $division[$l . '_by_party'];
    include '_dot_vote_list.php';

    if ($r) {
        $vote_title = $vote_sets[$r]['title'];
        $anchor = $vote_sets[$r]['anchor'];
        $summary = $division['party_breakdown'][$r];
        $votes = $division[$r . '_by_party'];
        include '_dot_vote_list.php';
    }

    $vote_title = $vote_sets[$l]['title'];
    $votes = $division[$l];
    include '_name_vote_list.php';

    if ($r) {
        $vote_title = $vote_sets[$r]['title'];
        $votes = $division[$r];
        include '_name_vote_list.php';
    }
}
