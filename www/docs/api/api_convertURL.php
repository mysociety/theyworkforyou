<?

function api_convertURL_front() {
?>
<p><big>Converts a parliament.uk Hansard URL into a TheyWorkForYou one, if possible.</big></p>

<h4>Arguments</h4>
<dl>
<dt>url</dt>
<dd>The parliament.uk URL you wish to convert, e.g.
<?	$db = new ParlDB;
	$q = $db->query('SELECT source_url FROM hansard WHERE major=1 AND hdate>"2006-07-01" ORDER BY RAND() LIMIT 1');
	print $q->field(0, 'source_url');
?></dd>
</dl>

<h4>Example Response</h4>
<pre>{ twfy : {
            gid : "uk.org.publicwhip/debate/2006-07-11a.1352.2",
            url : "http://www.theyworkforyou.com/debates/?id=2006-07-11a.1311.0#g1352.2"
         }
}</pre>

<h4>Example Use</h4>

<p><a href="javascript:function foo(r){if(r.twfy.url)window.location=r.twfy.url;};(function(){var s=document.createElement('script');s.setAttribute('src','http://theyworkforyou.com/api/convertURL?callback=foo&url='+encodeURIComponent(window.location));s.setAttribute('type','text/javascript');document.getElementsByTagName('head')[0].appendChild(s);})()">Hansard prettifier</a> - drag this bookmarklet to your bookmarks bar, or bookmark it. Then if you ever find yourself on the official site, clicking this will try and take you to the equivalent page on TheyWorkForYou. (Tested in IE, Firefox, Opera.)</p>
<?	
}

/* Very similar to function in hansardlist.php, but separated */
function get_listurl($q) {
	global $hansardmajors;
	$id_data = array(
		'gid' => fix_gid_from_db($q->field(0, 'gid')),
		'major' => $q->field(0, 'major'),
		'htype' => $q->field(0, 'htype'),
		'subsection_id' => $q->field(0, 'subsection_id'),
	);
	$db = new ParlDB;
	$LISTURL = new URL($hansardmajors[$id_data['major']]['page_all']);
	$fragment = '';
	if ($id_data['htype'] == '11' || $id_data['htype'] == '10') {
		$LISTURL->insert( array( 'id' => $id_data['gid'] ) );
	} else {
		$parent_epobject_id = $id_data['subsection_id'];
		$parent_gid = '';
		$r = $db->query("SELECT gid
				FROM 	hansard
				WHERE	epobject_id = '" . mysql_escape_string($parent_epobject_id) . "'
				");
		if ($r->rows() > 0) {
			$parent_gid = fix_gid_from_db( $r->field(0, 'gid') );
		}
		if ($parent_gid != '') {
			$LISTURL->insert( array( 'id' => $parent_gid ) );
			$fragment = '#g' . gid_to_anchor($id_data['gid']);
		}
	}
	return $LISTURL->generate('none') . $fragment;
}

function api_converturl_url_output($q) {
	$gid = $q->field(0, 'gid');
	$url = get_listurl($q);
	$output['twfy'] = array(
		'gid' => $gid,
		'url' => 'http://www.theyworkforyou.com' . $url
	);
	api_output($output);
}
function api_converturl_url($url) {
	$db = new ParlDB;
	$url_nohash = preg_replace('/#.*/', '', $url);
	$q = $db->query('select gid,major,htype,subsection_id from hansard where source_url = "' . mysql_escape_string($url) . '" order by gid limit 1');
	if ($q->rows())
		return api_converturl_url_output($q);

	$q = $db->query('select gid,major,htype,subsection_id from hansard where source_url like "' . mysql_escape_string($url_nohash) . '%" order by gid limit 1');
	if ($q->rows())
		return api_converturl_url_output($q);

	$url_bound = str_replace('cmhansrd/cm', 'cmhansrd/vo', $url_nohash);
	if ($url_bound != $url_nohash) {
		$q = $db->query('select gid,major,htype,subsection_id from hansard where source_url like "' . mysql_escape_string($url_bound) . '%" order by gid limit 1');
		if ($q->rows())
			return api_converturl_url_output($q);
	}
	api_error('Sorry, URL could not be converted');
}

?>
