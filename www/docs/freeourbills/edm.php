<?php

include_once "../../includes/easyparliament/init.php";
$this_page = 'campaign_edm';

$pc = get_http_var('pc');

if (get_http_var('wtt')) {
    $lookup = lookup($pc);
    if ($lookup['signed_edm']) {
        print <<<EOF
<p>SIGNED EDM TODO</p>
EOF;
    } else {
        print <<<EOF
<p>So your MP hasn't signed our Free Our Bills Early Day Motion, EDM
2141, eh? Here's some ideas for what to include in your email:

<ol>
<li>Please sign EDM 2141.
<li>Free Our Bills has been endorsed by party leaders David Cameron,
Nick Clegg and Alex Salmond, and over 100 MPs from all parties have
signed the EDM so far.
<li>Freeing our Bills will enable MPs, their assistants and regular
citizens to more easily find bills, discuss them, compare versions,
set up email alerts for words and phrases, and see really easily how
bills and amendments affect each other.
<li>It won't just be mySociety that builds new tools based on the data:
media companies and enthusiastic amateurs alike will be able to use
the data to improve understanding of what's going on in Parliament.
<li>That the only opposition that has come so far is from
technologically-challenged unelected officials: they shouldn't be
allowed to dictate to MPs and the public how Parliament works in the
Internet age.
<li>If you're feeling adventurous, you can ask for a meeting with your MP to
discuss it. We'll provide some briefing materials if you do go.
</ol>
EOF;
    }
    exit;
}

$PAGE->page_start();
$PAGE->stripe_start();

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
