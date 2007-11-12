<?

function api_getMPInfo_front() {
?>
<p><big>Fetch extra information for a particular person.</big></p>

<h4>Arguments</h4>
<dl>
<dt>id</dt>
<dd>The person ID.</dd>
</dl>

<h4>Example Response</h4>

<?	
}

function api_getMPinfo_id($id) {
	$db = new ParlDB;
	$last_mod = 0;
	$q = $db->query("select data_key, data_value, lastupdate from personinfo
		where person_id = '" . mysql_escape_string($id) . "'");
	if ($q->rows()) {
		$output = array();
		for ($i=0; $i<$q->rows(); $i++) {
			$output[$q->field($i, 'data_key')] = $q->field($i, 'data_value');
			$time = strtotime($q->field($i, 'lastupdate'));
			if ($time > $last_mod)
				$last_mod = $time;
		}
		$q = $db->query("select * from memberinfo
			where member_id in (select member_id from member where person_id = '" . mysql_escape_string($id) . "')");
		if ($q->rows()) {
			$oldmid = 0; $count = -1;
			for ($i=0; $i<$q->rows(); $i++) {
				$mid = $q->field($i, 'member_id');
				if (!isset($output['by_member_id'])) $output['by_member_id'] = array();
				if ($oldmid != $mid) {
					$count++;
					$oldmid = $mid;
					$output['by_member_id'][$count]['member_id'] = $mid;
				}
				$output['by_member_id'][$count][$q->field($i, 'data_key')] = $q->field($i, 'data_value');
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

?>
