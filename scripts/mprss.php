<?php

// Generates the RSS feeds for currently sitting MPs.

include '../www/includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

// Where all the RSS feeds go.
$rsspath = BASEDIR . '/rss/mp/';

// Make things group writable.
umask (002);

$HANSARDLIST = new HANSARDLIST();
$db = $HANSARDLIST->db;

// Get all the person ids we need feeds for...
$q = $db->query("SELECT person_id, group_concat(member_id order by member_id separator ',') as member_ids
			FROM member GROUP BY person_id HAVING max(left_house)='9999-12-31'");
if ($q->rows() <= 0) exit;

$starttime = time();
for ($personrow=0; $personrow<$q->rows(); $personrow++) {
	$person_id = $q->field($personrow, 'person_id');
	$member_ids = $q->field($personrow, 'member_ids');

	$args = array ( 'member_ids' => $member_ids );
	$speeches = $HANSARDLIST->display('person', $args, 'none');
		
	// Some data about this person that we'll need for the feed.
	$MEMBER = new MEMBER(array('person_id' => $person_id));
	$MPURL = new URL('mp');
	$MPURL->insert(array('pid'=>$person_id));
	$mpurl = $MPURL->generate();
		
	$date = gmdate('Y-m-d');
	$time = gmdate('H:i:s');
	$datenow = $date . 'T' . $time . '+00:00';
		
	// Prepare the meat of the RSS file.
	$items = '';
	$entries = '';
	if (isset ($speeches['rows']) && count($speeches['rows']) > 0) {
		
		foreach ($speeches['rows'] as $n => $row) {
		
			// While we're linking to individual speeches,
			// the text is the body of the parent, ie (sub)section.
			$title = _htmlentities(str_replace('&#8212;', '-', $row['parent']['body']));

			$link = isset($row['listurl']) ? $row['listurl'] : '';
			$link = 'http://' . DOMAIN . $link;
				
			$description = _htmlentities(trim_characters($row['body'], 0, 200));
			$contentencoded = $row['body'];
				
			$hdate = format_date($row['hdate'], 'Y-m-d');
			if ($row['htime'] != NULL) {
				$htime = format_time($row['htime'], 'H:i:s');
			} else {
				$htime = '00:00:00';
			}
				
			$date = $hdate . 'T' . $htime . '+00:00';
				
			$items .= '<rdf:li rdf:resource="' . $link . '" />' . "\n";
			$entries .= "<item rdf:about=\"$link\">
	<title>$title</title>
	<link>$link</link>
	<description>$description</description>
	<content:encoded><![CDATA[$contentencoded]]></content:encoded>
	<dc:date>$date</dc:date>
</item>
";
		
		}	
	}
		
	// Prepare the whole text of the RSS file.
	$rsstext = '<?xml version="1.0" encoding="iso-8859-1"?>
<rdf:RDF
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns="http://purl.org/rss/1.0/"
  xmlns:content="http://purl.org/rss/1.0/modules/content/">
		
<channel rdf:about="http://' . DOMAIN . $mpurl . '">
<title>' . entities_to_numbers($MEMBER->full_name()) . '\'s recent appearances (TheyWorkForYou)</title>
<link>http://' . DOMAIN . $mpurl . '</link>
<description></description>
<dc:language>en-gb</dc:language>
<dc:creator>TheyWorkForYou.com</dc:creator>
<dc:date>' . $datenow . '</dc:date>

<items>
<rdf:Seq>
' . $items . '</rdf:Seq>
</items>

</channel>

' . $entries . '

</rdf:RDF>';
		
	// Write the text to the file...
	$filename = $rsspath . $person_id . '.rdf';
	$fh = @fopen($filename, "w");
	if (!$fh) { # Problem writing, just carry on
		echo "Could not write to file ($filename)\n";
		continue;
	}
	fwrite($fh, $rsstext);
	fclose ($fh);
}

#print "Took " . (time()-$starttime) . " seconds\n";

?>
