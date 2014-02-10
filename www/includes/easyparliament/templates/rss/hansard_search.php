<?php global $SEARCHENGINE; header("Content-Type: text/xml; charset=iso-8859-1"); print '<?xml version="1.0" encoding="iso-8859-1"?>'; ?>

<rss version="2.0" xmlns:openSearch="http://a9.com/-/spec/opensearchrss/1.0/">
<channel>
<title>TheyWorkForYou.com Search: <?=$SEARCHENGINE->query_description_short() ?></title>
<link>http://www.theyworkforyou.com<?=htmlentities(str_replace('rss/', '', $_SERVER['REQUEST_URI'])) ?></link>
<description>Search results for <?=$SEARCHENGINE->query_description_short() ?> at TheyWorkForYou.com</description>
<language>en-gb</language>
<copyright>Parliamentary Copyright.</copyright>
<?php if (isset($data['info']['total_results'])) { ?>
<openSearch:totalResults><?=$data['info']['total_results'] ?></openSearch:totalResults>
<?php }
    if (isset($data['info']['first_result'])) { ?>
<openSearch:startIndex><?=$data['info']['first_result'] ?></openSearch:startIndex>
<?php }
    if (isset($data['info']['results_per_page'])) { ?>
<openSearch:itemsPerPage><?=$data['info']['results_per_page'] ?></openSearch:itemsPerPage>
<?php }

global $this_page;
twfy_debug("TEMPLATE", "rss/hansard_search.php");

if (isset ($data['rows']) && count($data['rows']) > 0) {
    for ($i=0; $i<count($data['rows']); $i++) {
        $row = $data['rows'][$i];

        $hdate = format_date($row['hdate'], 'D, d M Y');
        if (isset($row['htime']) && $row['htime'] != NULL) {
            $htime = format_time($row['htime'], 'H:i:s');
        } else {
            $htime = '00:00:00';
        }
        $date = "$hdate $htime +0000";
?>
<item>
<title><?php
        if (isset($row['parent']) && count($row['parent']) > 0) {
            echo strip_tags($row['parent']['body']);
        }
        echo (' (' . format_date($row['hdate'], SHORTDATEFORMAT) . ')');
?></title>
<link>http://www.theyworkforyou.com<?=$row['listurl'] ?></link>
<pubDate><?=$date ?></pubDate>
<description><?php
        if (isset($row['speaker']) && count($row['speaker'])) {
            $sp = $row['speaker'];
            $name = ucfirst(member_full_name($sp['house'], $sp['title'], $sp['first_name'], $sp['last_name'], $sp['constituency']));
            echo entities_to_numbers($name) . ': ';
        }
        echo htmlspecialchars(str_replace(array('&#8212;', '<span class="hi">', '</span>'), array('-', '<b>', '</b>'), $row['extract'])) . "</description>\n</item>\n";
    }
}
?>
</channel>
</rss>
