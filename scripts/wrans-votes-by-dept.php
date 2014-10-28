<?php

include '../www/includes/easyparliament/init.php';

$db = new \MySociety\TheyWorkForYou\ParlDb;

$q = $db->query('select count(*) as c from hansard where major=3 and minor=2');
$answers = $q->field(0, 'c');

# Done directly as storing everything in an array as $db->query() does runs out of memory, unsurprisingly
$q = mysql_query(
	'select section.body, gid, yes_votes, no_votes
	from hansard
		inner join epobject as section on hansard.section_id = section.epobject_id
		left join anonvotes on hansard.epobject_id = anonvotes.epobject_id
	where
		major = 3 and minor = 2
');

#$rows = mysql_num_rows($q);
#echo "Number of answers with votes: $rows\n";
#echo "Number of answers in system: $answers\n";

$votes = array();
while ($row = mysql_fetch_assoc($q)) {
	$dept = $row['body'];
	if (!isset($votes[$dept])) $votes[$dept] = array(
		'verymoreyes'=>0, 'moreyes'=>0, 'verymoreno'=>0, 'moreno'=>0, 'same'=>0, 'none'=>0
	);
	$yes = $row['yes_votes'];
	$no = $row['no_votes'];
	$gid = $row['gid'];
	if (is_null($yes)) {
		$votes[$dept]['none']++;
	} else {
		if ($no > $yes+10) {
			$votes[$dept]['verymoreno']++;
		} elseif ($no > $yes) {
			$votes[$dept]['moreno']++;
		} elseif ($no + 10 < $yes) {
			$votes[$dept]['verymoreyes']++;
		} elseif ($no < $yes) {
			$votes[$dept]['moreyes']++;
		} else {
			$votes[$dept]['same']++;
		}
	}
}

print "Department,11+ more yes votes than no,1-10 more yes votes than no,11+ more no votes than yes,1-10 more no votes than yes,Same votes yes/no,No votes\n";
foreach ($votes as $dept => $v) {
	#if (!$v['moreyes'] && !$v['moreno'] && !$v['same']) continue;
	if (strstr($dept, ','))
		$dept = "\"$dept\"";
	print "$dept,$v[verymoreyes],$v[moreyes],$v[verymoreno],$v[moreno],$v[same],$v[none]\n";
}
