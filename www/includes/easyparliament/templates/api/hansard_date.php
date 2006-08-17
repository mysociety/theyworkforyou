<?php
// For displaying the main Hansard content listings (by date) for the API.
// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.
// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of hansard_gid for information about its structure and contents...

# This is basically copied from the HTML template of the same name, hmm.

global $hansardmajors;

if (isset ($data['rows'])) {
	$out = array();
	for ($i=0; $i<count($data['rows']); $i++) {
		$row = $data['rows'][$i];

		if ($row['htype'] == '10' && isset($row['excerpt']) && strstr($row['excerpt'], "was asked&#8212;")) {
			// We fake it here. We hope this section only has a single line like
			// "The Secretary of State was asked-" and we don't want to make it a link.
			$has_content = false;
		} elseif (isset($row['contentcount']) && $row['contentcount'] > 0) {
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
		
		if ($row['htype'] == '10') {
			$out[] = array('entry' => $entry, 'subs' => array());
		} else {
			$out[sizeof($out)-1]['subs'][] = $entry;
		}

	}
	api_output($out);
} else {
	api_error('No data to display');
}

?>
