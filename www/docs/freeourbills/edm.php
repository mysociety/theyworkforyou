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
    $handle = fopen('/data/vhost/www.theyworkforyou.com/dumps/edm_status.csv', 'r');
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        list($pid, $name, $party, $const, $signed, $modcom, $minister) = $data;
	if ($pid == $mp['person_id']) {
	    $out = array('signed_edm'=>$signed, 'modcom'=>$modcom, 'minister'=>$minister);
	    break;
	}
    }
    fclose($handle);

    return $out;
}
