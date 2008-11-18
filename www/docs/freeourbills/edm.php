<?php
include_once "../../includes/easyparliament/init.php";

$this_page = 'campaign_edm';
$PAGE->page_start();
$PAGE->stripe_start();

$pc = get_http_var('pc');
if ($pc) {
    $lookup = lookup($pc);
    print '<pre>';
    print_r($lookup);
    print '</pre>';
}

?>

<form action="./edm" method="get">
<p>Postcode: <input type="text" name="pc" value="<?=htmlspecialchars($pc)?>">
<input type="submit" value="Look up"></p>
</form>
<?

$PAGE->stripe_end();
$PAGE->page_end();

function lookup($pc) {
    $key = 'Gbr9QgCDzHExFzRwPWGAiUJ5';
    $file = file_get_contents('http://www.theyworkforyou.com/api/getMP?output=php&key='.$key.'&postcode='.urlencode($pc));
    $mp = unserialize($file);
    if (isset($mp['error'])) {
        print 'ERROR: '. $mp['error'];
	return;
    }

    $out = array();
    foreach ($mp['office'] as $off) {
        if ($off['dept'] == 'Modernisation of the House of Commons Committee') {
	    $out['modcom'] = true;
	}
	if ($off['source'] == 'chgpages/govposts') {
	    $out['minister'] = true;
	}
	if ($off['source'] == 'chgpages/offoppose') {
	    $out['tory_minister'] = true;
	}
	if ($off['source'] == 'chgpages/libdem') {
	    $out['ld_minister'] = true;
	}
	if ($off['source'] == 'chgpages/privsec') {
	    $out['pps'] = true;
	}
    }

    $member_id = $mp['member_id'];
    $name = $mp['full_name'];

    $found = false;
    $mps_signed = file('EDMsigned');
    foreach ($mps_signed as $mp) {
    	preg_match('#^(\*|\^)?.*?(\d+)#', $mp, $m);
	$special = $m[1];
	$id = $m[2];
	if ($id == $member_id) {
	    $found = true;
	    break;
	}
    }

    $out['signed_edm'] = $found;
    return $out;
}
