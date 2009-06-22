<?php

include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/member.php";

$db = new ParlDB();

$PAGE->page_start();
$PAGE->stripe_start();

?>

<p>The <strong>new Speaker</strong> will be extremely important in making
Parliament more transparent, so that sites like this one can help people like
you understand more about <strong>what your MP is doing</strong>.</p>

<p>mySociety is asking likely candidates for the post of Speaker to endorse the
following principles.</p>
     
<p><strong>The three principles are:</strong></p>

<ol>

<li> Voters have the right to know in <strong>detail about the money</strong>
that is spent to support MPs and run Parliament, and in similar detail how the
decisions to spend that money are settled upon. </li>

<li> Bills being considered must be published online in a much better way than
they are now, as the <strong>Free Our Bills</strong> campaign has been
suggesting for some time. </li>

<li> The Internet is not a threat to a renewal in our democracy, it is one of
its best hopes. Parliament should appoint a senior officer with direct working
experience of the <strong>power of the Internet</strong> who reports directly
to the Speaker, and who will help Parliament adapt to a new era of transparency
and effectiveness. </li>

</ol>

<h2>Summary of responses</h2>

<p>Follow the MP link to read their response, if we have one.</p>

<?

$q = $db->query("select personinfo.person_id, first_name, last_name from personinfo, member
	where personinfo.person_id=member.person_id and left_house='9999-12-31'
	and data_key='is_speaker_candidate' and data_value=1");
$pids = array();
for ($i=0; $i<$q->rows(); $i++) {
	$pid = $q->field($i, 'person_id');
	$pids[] = $pid;
	$member[$pid] = new MEMBER(array('person_id' => $pid));
}

$pids_str = join(',', $pids);
$q = $db->query("select personinfo.person_id, data_value, last_name from personinfo, member
	where personinfo.person_id=member.person_id and left_house='9999-12-31'
	and personinfo.person_id in ($pids_str) and data_key = 'speaker_candidate_response_summary'
	order by last_name");
echo '<table>';
$oldpid = null;
for ($i=0; $i<$q->rows(); $i++) {
	$pid = $q->field($i, 'person_id');
	$value = $q->field($i, 'data_value');
	if ($pid != $oldpid) {
		if ($oldpid) print "</tr>\n";
		print '<tr><th align="left"><a href="' . $member[$pid]->url() . '">' . $member[$pid]->full_name() . '</a></th>';
		$oldpid = $pid;
	}
	if ($value)
		echo "<td>$value</td>";
	else
		echo "<td>No response</td>";
}

echo '</tr></table>';

$PAGE->stripe_end(array(
	array('type'=>'include', 'content'=>'donate')
));
$PAGE->page_end();

