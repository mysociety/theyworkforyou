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
<p>So, your MP hasn't signed our Free Our Bills EDM yet, eh? Here's some ideas for what to tell your MP.</p>
<ol>
<li>The campaign's been endorsed by David Cameron, Nick Clegg, Alex Salmond and a whole bunch of Labour MPs.
<li>That the campaign isn't just about helping mySociety, it's to help everyone from MP's assistants to newspaper editors. There are many people who'll be able to benefit from better bills.
<li>The process by which bills are going to be made, and the language in them doesn't have to change one bit. It's all about the way in which it's published when the ink is dry at the end of each day.
<li>The services that can be built include, but are not limited to: email alerts for keywords in bills, easier searching through and linking to bills and amendments, plus comparisons between versions of bills and tools to make it easier to understand how an amendment or a bill effects other documents, like Acts.
<li>That the only opposition that has come so far is from unelected officials: they shouldn't be allowed to dicatate how Parliament runs.
<li>If you're feeling adventurous, you can ask for a meeting with your MP to discuss it. We'll provide some briefing materials if you do go.
</ol>

<p>Just remember, please don't just cut and paste the above and hit send, do try to put in some words and views of your own - your MP is much more likely to respect your views.</p>
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
