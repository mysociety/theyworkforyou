<?php
// This sidebar is used on every Site News pages.

$ARCHIVEURL = new \MySociety\TheyWorkForYou\Url('sitenews_archive');
$url = $ARCHIVEURL->generate();

$this->block_start(['title' => 'News Archives', 'url' => $url]);
include BASEDIR . '/news/sidebar_archives.php';
$this->block_end();

$this->block_start(['title' => 'RSS/XML']);
$RSSURL = new \MySociety\TheyWorkForYou\Url('sitenews_rss1');
$rssurl = $RSSURL->generate();

$HELPURL = new \MySociety\TheyWorkForYou\Url('help');
$helpurl = $HELPURL->generate() . '#rss';
?>

<p><a href="<?php echo $rssurl; ?>">RSS feed of recent posts</a> (<a href="<?php echo $helpurl; ?>">?</a>)</p>

<?php
$this->block_end();
?>
