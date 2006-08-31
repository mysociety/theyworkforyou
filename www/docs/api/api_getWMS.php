<?

include_once 'api_getHansard.php';

function api_getWMS_front() {
?>
<p><big>Fetch Written Ministerial Statements.</big></p>

<h4>Arguments</h4>
<p>Note you can only supply <strong>one</strong> of the following at present.</p>
<dl>
<dt>date</dt>
<dd>Fetch the written ministerial statements for this date.</dd>
<dt>search</dt>
<dd>Fetch the written ministerial statements that contain this term.</dd>
<dt><s>department</s></dt>
<dd><s>Fetch the written ministerial statements by a particular department.</s></dd>
<dt>person</dt>
<dd>Fetch the written ministerial statements by a particular person ID.</dd>
<dt>gid</dt>
<dd>Fetch the written ministerial statement(s) that matches this GID.</dd>
<dt>order (optional, when using search or person)</dt>
<dd><kbd>d</kbd> for date ordering, <kbd>r</kbd> for relevance ordering.</dd>
<dt>page (optional, when using search or person)</dt>
<dd>Page of results to return.</dd>
<dt>num (optional, when using search or person)</dt>
<dd>Number of results to return.</dd>
</dl>

<h4>Example Response</h4>
<pre>
&lt;twfy&gt;
	...
	&lt;match&gt;
		&lt;entry&gt;
			&lt;epobject_id&gt;10465207&lt;/epobject_id&gt;
			&lt;htype&gt;10&lt;/htype&gt;
			&lt;gid&gt;2005-10-27a.13WS.0&lt;/gid&gt;
			&lt;hpos&gt;4&lt;/hpos&gt;
			&lt;section_id&gt;0&lt;/section_id&gt;
			&lt;subsection_id&gt;0&lt;/subsection_id&gt;
			&lt;hdate&gt;2005-10-27&lt;/hdate&gt;
			&lt;htime&gt;&lt;/htime&gt;
			&lt;source_url&gt;http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm051027/wmstext/51027m01.htm#51027m01.html_dpthd0&lt;/source_url&gt;
			&lt;major&gt;4&lt;/major&gt;
			&lt;body&gt;Deputy Prime Minister&lt;/body&gt;
		&lt;/entry&gt;
		&lt;subs&gt;
			&lt;arr2&gt;
				&lt;epobject_id&gt;10465208&lt;/epobject_id&gt;
				&lt;htype&gt;11&lt;/htype&gt;
				&lt;gid&gt;2005-10-27a.13WS.1&lt;/gid&gt;
				&lt;hpos&gt;5&lt;/hpos&gt;
				&lt;section_id&gt;10465207&lt;/section_id&gt;
				&lt;subsection_id&gt;0&lt;/subsection_id&gt;
				&lt;hdate&gt;2005-10-27&lt;/hdate&gt;
				&lt;htime&gt;&lt;/htime&gt;
				&lt;source_url&gt;http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm051027/wmstext/51027m01.htm#51027m01.html_sbhd0&lt;/source_url&gt;
				&lt;major&gt;4&lt;/major&gt;
				&lt;body&gt;Disabled Facilities Grant&lt;/body&gt;
				&lt;excerpt&gt;...&lt;/excerpt&gt;
				&lt;listurl&gt;/wms/?id=2005-10-27a.13WS.1&lt;/listurl&gt;
				&lt;commentsurl&gt;/wms/?id=2005-10-27a.13WS.1&lt;/commentsurl&gt;
				&lt;totalcomments&gt;0&lt;/totalcomments&gt;
				&lt;comment&gt;&lt;/comment&gt;
			&lt;/arr2&gt;
			&lt;arr2&gt;
				&lt;epobject_id&gt;10465210&lt;/epobject_id&gt;
				&lt;htype&gt;11&lt;/htype&gt;
				&lt;gid&gt;2005-10-27a.14WS.0&lt;/gid&gt;
				&lt;hpos&gt;7&lt;/hpos&gt;
				&lt;section_id&gt;10465207&lt;/section_id&gt;
				&lt;subsection_id&gt;0&lt;/subsection_id&gt;
				&lt;hdate&gt;2005-10-27&lt;/hdate&gt;
				&lt;htime&gt;&lt;/htime&gt;
				&lt;source_url&gt;http://www.publications.parliament.uk/pa/cm200506/cmhansrd/cm051027/wmstext/51027m01.htm#51027m01.html_sbhd1&lt;/source_url&gt;
				&lt;major&gt;4&lt;/major&gt;
				&lt;body&gt;Planning Regulations (Antennas, including Satellite Dishes)&lt;/body&gt;
				&lt;excerpt&gt;...&lt;/excerpt&gt;
				&lt;listurl&gt;/wms/?id=2005-10-27a.14WS.0&lt;/listurl&gt;
				&lt;commentsurl&gt;/wms/?id=2005-10-27a.14WS.0&lt;/commentsurl&gt;
				&lt;totalcomments&gt;0&lt;/totalcomments&gt;
				&lt;comment&gt;&lt;/comment&gt;
			&lt;/arr2&gt;
		&lt;/subs&gt;
	&lt;/match&gt;
	...
</pre>
<?
}

function api_getWMS_date($d) {
	_api_getHansard_date('WMS', $d);
}
function api_getWMS_year($y) {
	_api_getHansard_year('WMS', $y);
}
function api_getWMS_search($s) {
	_api_getHansard_search( array(
		's' => $s,
		'pid' => get_http_var('person'),
		'type' => 'wms',
	) );
}
function api_getWMS_person($pid) {
	_api_getHansard_search(array(
		'pid' => $pid,
		'type' => 'wms',
	));
}
function api_getWMS_gid($gid) {
	_api_getHansard_gid('WMS', $gid);
}
function api_getWMS_department($dept) {
	_api_getHansard_department('WMS', $dept);
}

?>
