<?php

// For displaying the main Hansard content listings (by gid),
// and individual Hansard items (the comments are handled separately
// by COMMENTLIST and the comments.php template).

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

global $this_page, $hansardmajors;

if (!isset($data['info'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$out = [];
if (isset($data['rows'])) {
    for ($i = 0; $i < count($data['rows']); $i++) {
        $row = $data['rows'][$i];
        if (count($row) == 0) {
            continue;
        }
        if ($row['htype'] == '12') {
            if (isset($row['speaker']) && count($row['speaker']) > 0) {
                $speaker = $row['speaker'];
                [$image, $sz] = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($speaker['person_id'], true);
                if ($image) {
                    $row['speaker']['image'] = $image;
                }
                $desc = '';
                if (isset($speaker['office'])) {
                    $desc = [];
                    foreach ($speaker['office'] as $off) {
                        $desc[] = $off['pretty'];
                    }
                    $desc = join(', ', $desc) . '; ';
                }

                if ($speaker['house'] == HOUSE_TYPE_COMMONS && $speaker['party'] != 'Speaker' && $speaker['party'] != 'Deputy Speaker' && $speaker['constituency']) {
                    $desc .= $speaker['constituency'] . ', ';
                }
                $desc .= _htmlentities($speaker['party']);
                if ($desc) {
                    $row['speaker']['desc'] = $desc;
                }
            }
        }
        $out[] = $row;
    }

    if (isset($data['subrows'])) {
        foreach ($data['subrows'] as $row) {
            if (isset($row['contentcount']) && $row['contentcount'] > 0) {
                $has_content = true;
            } elseif ($row['htype'] == '11' && $hansardmajors[$row['major']]['type'] == 'other') {
                $has_content = true;
            } else {
                $has_content = false;
            }
            $entry = $row;
            if (isset($row['excerpt'])) {
                $entry['excerpt'] = trim_characters($entry['excerpt'], 0, 200);
            }
            if ($has_content) {
            } else {
                unset($entry['listurl']);
                unset($entry['commentsurl']);
                unset($entry['comment']);
                unset($entry['totalcomments']);
            }
            $out[] = $entry;
        }
    }
    api_output($out);
} else {
    api_error('Nothing');
}
