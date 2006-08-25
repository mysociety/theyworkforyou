<?php
// For displaying the main Hansard content listings (by gid), 
// and individual Hansard items (the comments are handled separately
// by COMMENTLIST and the comments.php template).

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

global $this_page, $hansardmajors;

if (!isset($data['info'])) { header("HTTP/1.0 404 Not Found"); exit; }

$out = array();
if (isset ($data['rows'])) {
	for ($i=0; $i<count($data['rows']); $i++) {
		$row = $data['rows'][$i];
		if (count($row) == 0) continue;
		if ($row['htype'] == '12') {
			if (isset($row['speaker']) && count($row['speaker']) > 0) {
				$speaker = $row['speaker'];
				if (is_file(BASEDIR . IMAGEPATH . 'mps/' . $speaker['person_id'] . '.jpeg')) {
					$row['speaker']['image'] = IMAGEPATH . 'mps/' . $speaker['person_id'] . '.jpeg';
				} elseif (is_file(BASEDIR . IMAGEPATH . 'mps/' . $speaker['person_id'] . '.jpg')) {
					$row['speaker']['image'] = IMAGEPATH . 'mps/' . $speaker['person_id'] . '.jpg';
				}
				$desc = '';
				if (isset($speaker['office'])) {
					$desc = $speaker['office'][0]['pretty'];
					if (strpos($desc, 'PPS')!==false) $desc .= ', ';
				}
				if (!$desc || strpos($desc, 'PPS')!==false) {
					if ($speaker['house'] == 1 && $speaker['party'] != 'Speaker' && $speaker['party'] != 'Deputy Speaker' && $speaker['constituency']) {
						$desc .= $speaker['constituency'] . ', ';
					}
					$desc .= htmlentities($speaker['party']);
				}
				if ($desc) $row['speaker']['desc'] = $desc;
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
			if (isset($row['excerpt']))
				$entry['excerpt'] = trim_characters($entry['excerpt'], 0, 200);
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
}  else {
	api_error('Nothing');
   }

?>
