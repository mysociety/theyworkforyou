<?php

include '/data/vhost/www.theyworkforyou.com/includes/easyparliament/init.php';
# include INCLUDESPATH . 'easyparliament/member.php';

$db = new ParlDB();

$q = $db->query(
    'select person_id,
		count(hansard.epobject_id) as wrans,
		sum(yes_votes) + IFNULL((select sum(vote) from uservotes, hansard as hansard2
				  where uservotes.epobject_id=hansard2.epobject_id
					and hansard2.person_id=hansard.person_id), 0) as yes,
        sum(no_votes) + IFNULL((select count(vote)-sum(vote) from uservotes, hansard as hansard2
				 where uservotes.epobject_id=hansard2.epobject_id
                    and hansard2.person_id=hansard.person_id), 0) as no
	from hansard
		left join anonvotes on hansard.epobject_id=anonvotes.epobject_id
		where major = 3 and minor = 2 and left_house > curdate()
	group by person_id'
);

foreach ($q as $row) {
    $p_id = $row['person_id'];
    $name = $row['first_name'] . ' ' . $row['last_name'];
    $con = $row['constituency'];
    $wrans = $row['wrans'];
    $yes = $row['yes'];
    $no = $row['no'];
    if ($p_id) {
        $qq = $db->query('(select hansard.epobject_id from hansard, uservotes
			where hansard.epobject_id=uservotes.epobject_id
            and hansard.person_id = ' . $p_id . ' and major=3 and minor=2 and left_house>curdate())
		union
			(select hansard.epobject_id from hansard,member,anonvotes
			 where hansard.epobject_id=anonvotes.epobject_id
             and hansard.person_id = ' . $p_id . ' and major=3 and minor=2 and left_house>curdate())');
        $wrans_with_votes = $qq->rows();
    } else {
        $wrans_with_votes = '';
    }
    print "$name\t$con\t$wrans\t$wrans_with_votes\t$yes\t$no\n";
}
