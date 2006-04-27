<?php

include '/data/vhost/staging.theyworkforyou.com/includes/easyparliament/init.php';
# include INCLUDESPATH . 'easyparliament/member.php';

$db = new ParlDB;

$q = $db->query(
	'select person_id, first_name, last_name, constituency,
		count(hansard.epobject_id) as wrans,
		sum(yes_votes) + IFNULL((select sum(vote) from uservotes, hansard, member as member2
				  where uservotes.epobject_id=hansard.epobject_id
					and hansard.speaker_id=member2.member_id
					and member.person_id=member2.person_id), 0) as yes,
		sum(no_votes) + IFNULL((select count(vote)-sum(vote) from uservotes, hansard, member as member2
				 where uservotes.epobject_id=hansard.epobject_id
					and hansard.speaker_id=member2.member_id
					and member.person_id=member2.person_id), 0) as no
	from hansard
		left join member on hansard.speaker_id=member.member_id
		left join anonvotes on hansard.epobject_id=anonvotes.epobject_id
		where major = 3 and minor = 2 and left_house > curdate()
	group by person_id
	order by first_name, last_name, constituency');

for ($i=0; $i<$q->rows(); $i++) {
	$p_id = $q->field($i, 'person_id');
	$name = $q->field($i, 'first_name') . ' ' . $q->field($i, 'last_name');
	$con = $q->field($i, 'constituency');
	$wrans = $q->field($i, 'wrans');
	$yes = $q->field($i, 'yes');
	$no = $q->field($i, 'no');
	if ($p_id) {
	$qq = $db->query('(select hansard.epobject_id from hansard,member,uservotes
			where hansard.epobject_id=uservotes.epobject_id
				and hansard.speaker_id=member.member_id
				and person_id = '.$p_id.' and major=3 and minor=2 and left_house>curdate())
		union
			(select hansard.epobject_id from hansard,member,anonvotes
			 where hansard.epobject_id=anonvotes.epobject_id
			 	and hansard.speaker_id=member.member_id
				and person_id = '.$p_id.' and major=3 and minor=2 and left_house>curdate())');
	$wrans_with_votes = $qq->rows();
	} else {
		$wrans_with_votes = '';
	}
	print "$name\t$con\t$wrans\t$wrans_with_votes\t$yes\t$no\n";
}
