#!/usr/bin/php -q
<?php
/*
 * Name: daily-api-usage.php
 * Description: Summary of the day's API usage
 */

include_once dirname(__FILE__) . '/../www/includes/easyparliament/init.php';

$db = new ParlDB;
$q = $db->query('SELECT
    api_key.api_key, api_key.commercial, DATE(api_key.created), api_key.reason,
    users.firstname, users.lastname, users.email,
    count(distinct(ip_address)) as ip_addresses, count(*) AS count
    FROM api_stats, api_key, users
    WHERE api_stats.api_key = api_key.api_key
        AND users.user_id = api_key.user_id
        AND query_time >= subdate(current_date, 1)
        AND query_time < current_date
    GROUP BY api_key
    ORDER BY count DESC
');

$keys = array();
for ($i=0; $i<$q->rows(); $i++) {
    $keys[] = $q->field($i, 'api_key');
}
$keys = join("','", $keys);

$q2 = $db->query("SELECT
    api_key, DATE(MIN(query_time)) AS first_use, COUNT(*) AS count
    FROM api_stats
    WHERE api_key IN ('$keys')
    GROUP BY api_key
");
$summary = array();
for ($i=0; $i<$q2->rows(); $i++) {
    $row = $q2->row($i);
    $summary[$row['api_key']] = array($row['first_use'], $row['count']);
}

$out = '';
for ($i=0; $i<$q->rows(); $i++) {
    $row = $q->row($i);
    $reason = preg_replace("/\r?\n/", ' ', $row['reason']);
    $comm = $row['commercial']==1 ? ', commercial' : '';
    $ipa = $row['ip_addresses']!=1 ? 'es' : '';
    $hp = $row['count']!=1 ? 's' : '';
    list($first, $total) = $summary[$row['api_key']];
    $out .= "<p><b>$row[count] hit$hp, from $row[ip_addresses] IP address$ipa.</b> $row[firstname] $row[lastname] &lt;$row[email]&gt;
<br>$row[api_key], created $row[created], first use $first$comm, total calls $total
<br><small style='color:#666'>$reason</small>
";
}
if (!$out) exit;

$headers =
    "From: TheyWorkForYou <" . CONTACTEMAIL . ">\r\n" .
    "Content-Type: text/html; charset=iso-8859-1\r\n" .
    "MIME-Version: 1.0\r\n" .
    "Content-Transfer-Encoding: 8bit\r\n";
$subject = 'Daily TheyWorkForYou API usage';
$to = join(chr(64), array('commercial', 'mysociety.org'));
mail ($to, $subject, $out, $headers);

