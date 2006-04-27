<?php

// Generates the RSS feeds for currently sitting MPs.

include '/data/vhost/www.theyworkforyou.com/includes/easyparliament/init.php';
include INCLUDESPATH . 'easyparliament/member.php';

// Where all the RSS feeds go.
$rsspath = FILEPATH . 'rss/mp/';

// Make things group writable.
umask (002);

$HANSARDLIST = new HANSARDLIST();
$db = $HANSARDLIST->db;

// Get all the person ids we need feeds for...
$q = $db->query("SELECT person_id FROM member WHERE left_house = '9999-12-31'");
if ($q->rows() > 0) {
	
	for ($personrow=0; $personrow<$q->rows(); $personrow++) {
		$person_id = $q->field($personrow, 'person_id');
//		print $person_id." ";

		// Get all the recent Hansard appearances by this person.
		$args = array (
			'person_id' => $person_id,
			'max'	=> 10
		);
		$speeches = $HANSARDLIST->display('person', $args, 'none');
		
		// Some data about this person that we'll need for the feed.
		$MEMBER = new MEMBER(array ('person_id' => $person_id));
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
				$title = htmlentities(str_replace('&#8212;', '-', $row['parent']['body']));

				$link = isset($row['listurl']) ? $row['listurl'] : '';
				$link = 'http://' . DOMAIN . $link;
				
				if ($row['major'] == 1 && $row['total_speeches'] > 1) {
					// Debates with more than one speech.
					$plural = $row['total_speeches'] == 2 ? 'speech' : 'speeches';
					$num = $row['total_speeches'] - 1;
					$morespeeches = ' (And ' . $num . " more $plural in this debate.)";
				} else {
					$morespeeches = '';
				}
				
				$description = htmlentities(trim_characters($row['body'], 0, 200)) . $morespeeches;
				$contentencoded = $row['body'] . $morespeeches;
				
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
<title>' . $MEMBER->full_name() . '\'s Recent Appearances (TheyWorkForYou.com)</title>
<link>http://' . DOMAIN . $mpurl . '</link>
<description></description>
<dc:language>en-gb</dc:language>
<dc:creator>TheyWorkForYou.com, mprss.php script</dc:creator>
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
		$fh = fopen($filename, "w");
		if (fwrite($fh, $rsstext) === FALSE) {
			echo "Could not write to file ($filename)\n";
		}
		fclose ($fh);
	
	} // Stop cycling through people.
}

?>
