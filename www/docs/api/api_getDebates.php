<?

include_once 'api_getHansard.php';

function api_getDebates_front() {
?>
<p><big>Fetch Debates.</big></p>
<p>This includes Oral Questions.</p>
<h4>Arguments</h4>
<p>Note you can only supply <strong>one</strong> of the following search terms at present.</p>
<dl>
<dt>type (required)</dt>
<dd>One of "commons", "westminsterhall", or "lords".
<dt>date</dt>
<dd>Fetch the debates for this date.</dd>
<dt>search</dt>
<dd>Fetch the debates that contain this term.</dd>
<dt>person</dt>
<dd>Fetch the debates by a particular person ID.</dd>
<dt>gid</dt>
<dd>Fetch the speech or debate that matches this GID.</dd>
<dt>order (optional, when using search or person)</dt>
<dd><kbd>d</kbd> for date ordering, <kbd>r</kbd> for relevance ordering.</dd>
<dt>page (optional, when using search or person)</dt>
<dd>Page of results to return.</dd>
<dt>num (optional, when using search or person)</dt>
<dd>Number of results to return.</dd>
</dl>

<h4>Example Response (search)</h4>
<pre>{
	"info" : {
		"s" : "fish section:lords",
		"results_per_page" : 20,
		"page" : 1,
		"total_results" : 245,
		"first_result" : 1
	},
	"searchdescription" : "containing the word 'fish' in Lords debates",
	"rows" : [{
		"gid" : "2006-07-14a.946.0",
		"hdate" : "2006-07-14",
		"htype" : "12",
		"major" : "101",
		"section_id" : "11432880",
		"subsection_id" : "11432880",
		"relevance" : 17,
		"speaker_id" : "100176",
		"hpos" : "29",
		"body" : ...
		"listurl" : "/lords/?id=2006-07-14a.901.2&amp;s=fish+section%3Alords#g946.0",
		"speaker" : {
			"member_id" : "100176",
			"title" : "Lord",
			"first_name" : "Robert",
			"last_name" : "Dixon-Smith",
			"house" : "2",
			"constituency" : "",
			"party" : "Conservative",
			"person_id" : "13665",
			"url" : "/peer/?m=100176"
		},
		"parent" : {
			"body" : "Climate Change (EAC Report)"
		}
	},
	{
		"gid" : "2006-07-13a.874.0",
		"hdate" : "2006-07-13",
		"htype" : "12",
		"major" : "101",
		"section_id" : "11432688",
		"subsection_id" : "11432688",
		"relevance" : 28,
		"speaker_id" : "100549",
		"hpos" : "179",
		"body" : ...
		"listurl" : "/lords/?id=2006-07-13a.871.2&amp;s=fish+section%3Alords#g874.0",
		"speaker" : {
			"member_id" : "100549",
			"title" : "Lord",
			"first_name" : "Jeff",
			"last_name" : "Rooker",
			"house" : "2",
			"constituency" : "",
			"party" : "Labour",
			"person_id" : "10511",
			"url" : "/peer/?m=100549",
			"office" : [{
				"dept" : "Department for Environment, Food and Rural Affairs",
				"position" : "Minister of State (Sustainable Farming and Food)",
				"pretty" : "Minister of State (Sustainable Farming and Food), Department for Environment, Food and Rural Affairs"
			}]
		},
		"parent" : {
			"body" : "Northern Ireland (Miscellaneous Provisions) Bill"
		}
	},
	...</pre>
<?	
}

function api_getDebates_type($t) {
	if ($t == 'commons') {
		$list = 'DEBATE';
		$type = 'debates';
	} elseif ($t == 'lords') {
		$list = 'LORDSDEBATE';
		$type = 'lords';
	} elseif ($t == 'westminsterhall') {
		$list = 'WHALL';
		$type = 'whall';
	} else {
		api_error('Unknown type');
		return;
	}
	if ($d = get_http_var('date')) {
		_api_getHansard_date($list, $d);
	} elseif (get_http_var('search') || get_http_var('person')) {
		$s = get_http_var('search');
		$pid = get_http_var('person');
		_api_getHansard_search(array(
			's' => $s,
			'pid' => $pid,
			'type' => $type,
		));
	} elseif ($gid = get_http_var('gid')) {
		_api_getHansard_gid($list, $gid);
	} elseif ($y = get_http_var('year')) {
		_api_getHansard_year($list, $y);
	} else {
		api_error('That is not a valid search.');
	}
}

function api_getDebates_date($d) {
	api_error('You must supply a type');
}
function api_getDebates_search($s) {
	api_error('You must supply a type');
}
function api_getDebates_person($p) {
	api_error('You must supply a type');
}
function api_getDebates_gid($p) {
	api_error('You must supply a type');
}
?>
