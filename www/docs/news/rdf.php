<?php
header('Content-type: application/rss+xml');
include($_SERVER['DOCUMENT_ROOT'] . '/../includes/easyparliament/init.php');
require_once 'editme.php';
print '<?xml version="1.0" encoding="utf-8"?>' ?>

<rdf:RDF
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
  xmlns:admin="http://webns.net/mvcb/"
  xmlns:cc="http://web.resource.org/cc/"
  xmlns="http://purl.org/rss/1.0/">

<channel rdf:about="https://www.theyworkforyou.com/news/">
<title>TheyWorkForYou News</title>
<link>https://www.theyworkforyou.com/news/</link>
<description>The weblog for news about site updates, etc.</description>
<dc:language>en-us</dc:language>
<dc:creator></dc:creator>
<admin:generatorAgent rdf:resource="http://www.movabletype.org/?v=2.661" />

<items>
<rdf:Seq>
<?php
    $c = 0;
foreach ($all_news as $id => $news_row) {
    if ($c++ == 10) {
        break;
    }
    [$title, $content, $date] = $news_row;
    $url = "https://www.theyworkforyou.com" . news_individual_link($date, $title);
    print "<rdf:li rdf:resource=\"$url\" />\n";
}
?>
</rdf:Seq>
</items>

</channel>

<?php
    $c = 0;
foreach ($all_news as $id => $news_row) {
    if ($c++ == 10) {
        break;
    }
    [$title, $content, $date] = $news_row;
    $url = "https://www.theyworkforyou.com" . news_individual_link($date, $title);
    $excerpt = trim_characters(news_format_body($content), 0, 250);
    $date = str_replace(" ", "T", $date) . "+00:00";
    ?>
<item rdf:about="<?=$url?>">
<title><?=_htmlspecialchars($title)?></title>
<link><?=$url?></link>
<description><?=$excerpt?></description>
<dc:subject></dc:subject>
<dc:creator>theyworkforyou</dc:creator>
<dc:date><?=$date?></dc:date>
</item>

<?php
}
?>

</rdf:RDF>
