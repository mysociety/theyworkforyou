<?php
// This sidebar is used on every Site News pages.

$ARCHIVEURL = new URL('sitenews_archive');
$url = $ARCHIVEURL->generate();

$this->block_start(array('title'=>'News Archives', 'url'=>$url));
include BASEDIR . '/news/sidebar_archives.php';
$this->block_end();

$this->block_start(array('title'=>'RSS/XML'));
$RSSURL = new URL('sitenews_rss1');
$rssurl = $RSSURL->generate();

$HELPURL = new URL('help');
$helpurl = $HELPURL->generate() . '#rss';
?>

<p><a href="<?php echo $rssurl; ?>">RSS feed of recent posts</a> (<a href="<?php echo $helpurl; ?>">?</a>)</p>

<?php
$this->block_end();
?>
