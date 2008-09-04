<?

function api_getMPsInfo_front() {
?>
<p><big>Fetch extra information for particular people.</big></p>

<h4>Arguments</h4>
<dl>
<dt>ids</dt>
<dd>The person IDs, separated by commas.</dd>
<dt>fields (optional)</dt>
<dd>Which fields you want to return, comma separated (leave blank for all).</dd>
</dl>

<h4>Example Response</h4>

<?	
}

function api_getMPsInfo_id($ids) {
	$fields = preg_split('#\s*,\s*#', get_http_var('fields'), -1, PREG_SPLIT_NO_EMPTY);
	$ids = preg_split('#\s*,\s*#', $ids, -1, PREG_SPLIT_NO_EMPTY);
	$safe_ids = array(0);
	foreach ($ids as $id) {
		if (ctype_digit($id)) $safe_ids[] = $id;
	}
	$ids = join(',', $safe_ids);

	$db = new ParlDB;
	$last_mod = 0;
	$q = $db->query("select person_id, data_key, data_value, lastupdate from personinfo
		where person_id in (" . $ids . ")");
	if ($q->rows()) {
		$output = array();
		for ($i=0; $i<$q->rows(); $i++) {
			$data_key = $q->field($i, 'data_key');
			if (count($fields) && !in_array($data_key, $fields))
				continue;
			$pid = $q->field($i, 'person_id');
			$output[$pid][$data_key] = $q->field($i, 'data_value');
			$time = strtotime($q->field($i, 'lastupdate'));
			if ($time > $last_mod)
				$last_mod = $time;
		}
		$q = $db->query("select memberinfo.*, person_id from memberinfo, member
			where memberinfo.member_id=member.member_id and person_id in (" . $ids . ")
			order by person_id,member_id");
		if ($q->rows()) {
			$oldmid = 0; $count = -1;
			for ($i=0; $i<$q->rows(); $i++) {
				$data_key = $q->field($i, 'data_key');
				if (count($fields) && !in_array($data_key, $fields))
					continue;
				$mid = $q->field($i, 'member_id');
				$pid = $q->field($i, 'person_id');
				if (!isset($output[$pid]['by_member_id'])) $output[$pid]['by_member_id'] = array();
				if ($oldmid != $mid) {
					$count++;
					$oldmid = $mid;
					$output[$pid]['by_member_id'][$count]['member_id'] = $mid;
				}
				$output[$pid]['by_member_id'][$count][$data_key] = $q->field($i, 'data_value');
				$time = strtotime($q->field($i, 'lastupdate'));
				if ($time > $last_mod)
					$last_mod = $time;
			}
		}
		ksort($output);
		api_output($output, $last_mod);
	} else {
		api_error('Unknown person ID');
	}
}

function api_getMPsInfo_fields($f) {
	api_error('You must supply a person ID');
}

