<style>
.confirm-panel {
    margin-left: 18px;
    margin-bottom: 2em;
}

.confirm-panel input {
    font-size: 2em;
    font-weight: bold;
}
</style>
<?php

include_once '../../includes/easyparliament/init.php';

$this_page = 'admin_featured';

$db = new ParlDB;

$PAGE->page_start();
$PAGE->stripe_start();

$out = '';
$preview = 0;
if (get_http_var('preview')) {
    $preview = 1;
    $out = preview_featured();
} else if ( get_http_var('confirm') ) {
    $out = update_featured();
}

print '<div id="adminbody">';
print $out;
edit_featured_form();
print '</div>';

function gid_to_url($gid) {
    if ( !$gid ) {
        return '';
    }
    global $hansardmajors;
    global $db;

    $q = $db->query("SELECT major FROM hansard WHERE gid = :gid", array( ':gid' => $gid ));
    $url_gid = fix_gid_from_db($gid);
    $url = new \URL($hansardmajors[$q->field(0, 'major')]['page']);
    $url->insert(array('id' => $url_gid));
    $url = 'http://' . DOMAIN . $url->generate();
    return $url;
}

function edit_featured_form() {
    $featured = new MySociety\TheyWorkForYou\Model\Featured;
    $title = $featured->get_title();
    $gid = $featured->get_gid();
    $related = $featured->get_related();

    if ( get_http_var('url') ) {
        $url = get_http_var('url');
        $related1 = get_http_var('related1');
        $related2 = get_http_var('related2');
        $related3 = get_http_var('related3');
    } else {
        $url = gid_to_url($gid);
        $related1 = gid_to_url($related[0]);
        $related2 = gid_to_url($related[1]);
        $related3 = gid_to_url($related[2]);
    }

?>
    <form action="featured.php" method="POST">
        <input type="hidden" name="preview" value="1">

        <p>
        <label for="title">Title (optional):</label>
        <span class="formw"><input type="text" name="title" id="featured_title" value="<?= $title ?>"></span>
        </p>

        <p>
        <label for="url">debate/speech URL:</label>
        <span class="formw"><input type="text" name="url" id="url" size="70" value="<?= $url ?>"></span>
        </p>

        <p>
        <label for="url">related debate/speech URL:</label>
        <span class="formw"><input type="text" name="related1" id="related1" size="70" value="<?= $related1 ?>"></span>
        </p>

        <p>
        <label for="url">related debate/speech URL:</label>
        <span class="formw"><input type="text" name="related2" id="related2" size="70" value="<?= $related2 ?>"></span>
        </p>

        <p>
        <label for="url">related debate/speech URL:</label>
        <span class="formw"><input type="text" name="related3" id="related3" size="70" value="<?= $related3 ?>"></span>
        </p>

        <p>
        <span class="formw"><input name="btnaction" type="submit" value="Preview"</span>
        </p>
    </form>

<?php
}

function preview_featured() {
    $title = get_http_var('title');
    $url = get_http_var('url');
    $related1 = get_http_var('related1');
    $related2 = get_http_var('related2');
    $related3 = get_http_var('related3');

    $gid = $url ? get_gid_from_url($url) : NULL;
    $related_gid1 = $related1 ? get_gid_from_url($related1) : NULL;
    $related_gid2 = $related2 ? get_gid_from_url($related2) : NULL;
    $related_gid3 = $related3 ? get_gid_from_url($related3) : NULL;

    print "<h2>Preview Content</h2>";
    if ( $gid ) {
        $h = new MySociety\TheyWorkForYou\Homepage;
        $featured = $h->getFeaturedDebate($gid, $title, array( $related_gid1, $related_gid2, $related_gid3 ));

        include INCLUDESPATH . 'easyparliament/templates/html/homepage/featured.html';
    } else {
        print "<p>A random debate from the last 7 days will be displayed</p>";
    }

?>
<p><small>(Preview for content only, not look)</small></p>
<div class="confirm-panel">
    <form action="featured.php" method="POST">
        <input type="hidden" name="title" id="featured_title" value="<?= $title ?>"><br>
        <input type="hidden" name="confirm" id="confirm" value="1"><br>
        <input type="hidden" name="gid" id="gid" value="<?= $gid ?>"><br>
        <input type="hidden" name="related_gid1" id="related_gid1" value="<?= $related_gid1 ?>"><br>
        <input type="hidden" name="related_gid2" id="related_gid2" value="<?= $related_gid2 ?>"><br>
        <input type="hidden" name="related_gid3" id="related_gid3" value="<?= $related_gid3 ?>"><br>
        <span class="formw"><input name="btnaction" type="submit" value="Confirm"</span>
    </form>
</div>

<h2>Or</h2>
<?php
}

function get_gid_from_url($url) {
    $parts = parse_url($url);
    parse_str($parts['query'], $query);
    $gid = 'uk.org.publicwhip/debate/' . $query['id'];
    return $gid;
}

function update_featured() {
    $featured = new MySociety\TheyWorkForYou\Model\Featured;

    $out = '';
    $title = get_http_var('title');
    $gid = get_http_var('gid');
    $related1 = get_http_var('related_gid1');
    $related2 = get_http_var('related_gid2');
    $related3 = get_http_var('related_gid3');

    $featured->set_title($title);
    $featured->set_gid($gid);
    $featured->set_related(array($related1, $related2, $related3));

    $out = "<h4>update successful</h4>";
    $out .= "<p>Title set to $title and gid to $gid</p>";

    return $out;
}

$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
    array(
        'type'		=> 'html',
        'content'	=> $menu
    )
));

$PAGE->page_end();
