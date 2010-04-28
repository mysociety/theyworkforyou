<?php

$this_page = 'boundaries';
include_once "../../includes/easyparliament/init.php";
include_once '../../../commonlib/phplib/mapit.php';

$PAGE->page_start();
?>
<h2>General election constituency boundaries</h2>
<div id="boundaries">
<?

function create_map_filename($c) {
    $c = str_replace(array(',', '(', ')', "'"), '', $c);
    $c = str_replace('&', 'and', $c);
    $c = str_replace("\xf4", 'o', $c);
    $c = rawurlencode(strtolower($c));
    return $c;
}

$pc = get_http_var('pc');
if ($pc && !validate_postcode($pc)) {
    print '<p>Sorry, that doesn&rsquo;t appear to be a valid postcode.</p>';
    $pc = '';
}
if ($pc) {
    $xml = simplexml_load_string(@file_get_contents(POSTCODE_API_URL . urlencode($pc)));
	if (!$xml) {
    } elseif ($xml->error) {
		print "<p>Sorry, " . htmlentities($pc) . " isn't a known postcode (or our postcode lookup is temporarily not working).</p>";
        $pc = '';
    }
}
if ($pc) {
    $current = iconv('utf-8', 'iso-8859-1//TRANSLIT', (string)$xml->current_constituency);
    $new = iconv('utf-8', 'iso-8859-1//TRANSLIT', (string)$xml->future_constituency);
    $mp_name = iconv('utf-8', 'iso-8859-1//TRANSLIT', (string)$xml->current_mp_name);

    $current_disp = str_replace('&', 'and', $current);
    $map_url_current = create_map_filename($current);
    $map_url_new = create_map_filename($new);
?>

<div id="maps">
<h3>Maps</h3>
<?
    if (is_ni($new)) {
        print '<p><em>We don&rsquo;t currently have maps showing the new constituency boundary for Northern Irish constituencies.</em></p>';
    } elseif (is_scottish($new)) {
        print '<p><em>There are no boundary changes in Scotland for this election.</em></p>';
    } else {
        print "<p class='desc'>New $new constituency for May 6th:</p>";
        print "<p><img src='http://matthew.theyworkforyou.com/boundaries/maps_next/$map_url_new.png' alt='Map showing boundary of the $new constituency' width=400 height=400>";
    }
?>
<p class="desc"><? if (!is_scottish($new)) print 'Former c'; else print 'C'; ?>onstituency of <?=$current_disp?>:</p>
<p><img src='http://matthew.theyworkforyou.com/boundaries/maps_now/<?=$map_url_current?>.png' alt='Map showing boundary of the <?=$current_disp?> constituency' width=400 height=400>

<p class="footer"><small>Images produced from the Ordnance Survey <a href="http://www.election-maps.co.uk/" rel="nofollow">election-maps</a> service. Images reproduced with permission of <a href="http://www.ordnancesurvey.co.uk/" rel="nofollow">Ordnance Survey</a> and <a href="http://www.lpsni.gov.uk/" rel="nofollow">Land and Property Services</a>.</small></p>

</div>

<ul class="results">
<?
    $mp_url = '/mp/' . make_member_url($mp_name, $current, 1);
    if (is_scottish($new)) {
        print '<li>Scotland does not have any boundary changes, so you remain
        in your constituency of <strong>' . $current_disp . '</strong>; your MP
        was <a href="' . $mp_url . '">' . $mp_name . '</a>.';
    } else {
        if (isset($new)) {
            print '<li>For the general election, you are in the <strong>' . $new . '</strong> constituency.';
        } else {
            print '<li>We cannot look up the constituency for the election for some reason, sorry.';
        }
?>
<li>You were in the <strong><?=$current_disp?></strong> constituency; your MP was <a href='<?=$mp_url?>'><?=$mp_name?></a>.</p>
<?
    }
    echo '</ul>';
    if (isset($new) && $current_disp == $new && !is_scottish($new)) {
        print '<p>The constituency may have kept the same name but altered its boundaries &ndash; do check the maps on the right.</p>';
    }
}

if (!$pc) { ?>
<div class="picture">
<a href="http://www.flickr.com/photos/markybon/138214000/" title="Boundaries by MarkyBon, on Flickr"><img src="http://farm1.static.flickr.com/51/138214000_80327fe675.jpg" width="358" height="500" alt="Boundaries"></a>
<br><small>Boundaries by MarkyBon</small>
</div>
<?
}
?>

<p class="intro">Constituency
boundaries are <strong>changing</strong> for the 2010 general election in
England, Wales, and Northern Ireland. Enter your postcode here to find out
what constituency you were in, and what constituency you are now 
voting in at the election, along with maps of before and after.
</p>

<form method="get">
<p><label for="pc">Enter your UK postcode:</label>
<input type="text" id="pc" name="pc" value="<?=htmlspecialchars($pc)?>" size="7">
<input type="submit" value="Look up">
</p>
</form>

<p>This service should work anywhere in the UK, errors and omissions excepted.</p>

<p>This service is also available through the <a href="/api/">TheyWorkForYou API</a>
via the getConstituency method; you must credit TheyWorkForYou if you use this, and 
please contact us about commercial use.</p>

<p><big><em><a href="http://www.democracyclub.org.uk/">Join DemocracyClub</a> to help make
this coming election the most transparent ever!</em></big></p>

</div>
<?

$PAGE->page_end();

# ---

function is_scottish($c) {
    $const_scottish = array(
'Aberdeen North',
'Aberdeen South',
'Airdrie and Shotts',
'Angus',
'Argyll and Bute',
'Ayr, Carrick and Cumnock',
'Banff and Buchan',
'Berwickshire, Roxburgh and Selkirk',
'Caithness, Sutherland and Easter Ross',
'Central Ayrshire',
'Coatbridge, Chryston and Bellshill',
'Cumbernauld, Kilsyth and Kirkintilloch East',
'Dumfries and Galloway',
'Dumfriesshire, Clydesdale and Tweeddale',
'Dundee East',
'Dundee West',
'Dunfermline and West Fife',
'East Dunbartonshire',
'East Kilbride, Strathaven and Lesmahagow',
'East Lothian',
'East Renfrewshire',
'Edinburgh East',
'Edinburgh North and Leith',
'Edinburgh South',
'Edinburgh South West',
'Edinburgh West',
'Falkirk',
'Glasgow Central',
'Glasgow East',
'Glasgow North',
'Glasgow North East',
'Glasgow North West',
'Glasgow South',
'Glasgow South West',
'Glenrothes',
'Gordon',
'Inverclyde',
'Inverness, Nairn, Badenoch and Strathspey',
'Kilmarnock and Loudoun',
'Kirkcaldy and Cowdenbeath',
'Lanark and Hamilton East',
'Linlithgow and East Falkirk',
'Livingston',
'Midlothian',
'Moray',
'Motherwell and Wishaw',
'Na h-Eileanan an Iar',
'North Ayrshire and Arran',
'North East Fife',
'Ochil and South Perthshire',
'Orkney and Shetland',
'Paisley and Renfrewshire North',
'Paisley and Renfrewshire South',
'Perth and North Perthshire',
'Ross, Skye and Lochaber',
'Rutherglen and Hamilton West',
'Stirling',
'West Aberdeenshire and Kincardine',
'West Dunbartonshire',
);
    if (in_array((string)$c, $const_scottish)) return true;
    return false;
}

function is_ni($c) {
    $const_ni = array(
'Belfast East',
'Belfast North',
'Belfast South',
'Belfast West',
'East Antrim',
'East Londonderry',
'Fermanagh and South Tyrone',
'Foyle',
'Lagan Valley',
'Mid Ulster',
'Newry and Armagh',
'North Antrim',
'North Down',
'South Antrim',
'South Down',
'Strangford',
'Upper Bann',
'West Tyrone',
);
    if (in_array((string)$c, $const_ni)) return true;
    return false;
}
