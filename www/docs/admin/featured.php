<?php

include_once '../../includes/easyparliament/init.php';

$this_page = 'admin_featured';

$db = new ParlDB();

$PAGE->page_start();
$PAGE->stripe_start();

$out = '';
if (get_http_var('preview')) {
    preview_featured();
} elseif (get_http_var('confirm')) {
    $out = update_featured();
}

print '<div id="adminbody">';
print $out;
print '<p><b>This currently works for debates in the House of Commons or Lords, or Westminster Hall</b></p>';
edit_featured_form();
print '</div>';

function gid_to_url($gid) {
    if (!$gid) {
        return '';
    }
    global $hansardmajors;
    global $db;

    $q = $db->query("SELECT major FROM hansard WHERE gid = :gid", [ ':gid' => $gid ])->first();
    $url_gid = fix_gid_from_db($gid);
    $url = new \MySociety\TheyWorkForYou\Url($hansardmajors[$q['major']]['page']);
    $url->insert(['id' => $url_gid]);
    return $url->generate();
}

function edit_featured_form() {
    $featured = new MySociety\TheyWorkForYou\Model\Featured();
    $title = $featured->get_title();
    $gid = $featured->get_gid();
    $related = $featured->get_related();
    $context = $featured->get_context();

    if (get_http_var('url')) {
        $url = get_http_var('url');
        $title = get_http_var('title');
        $context = get_http_var('context');
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
        <span class="formw"><input type="text" name="title" id="featured_title" value="<?= _htmlspecialchars($title) ?>"></span>
        </p>

        <p>
        <label for="context">Context (optional):</label><br>
        <span class="formw"><textarea rows="5" cols="80" name="context" id="featured_context"><?= _htmlspecialchars($context) ?></textarea></span>
        </p>

        <p>
        <label for="url">debate/speech URL:</label>
        <span class="formw"><input type="text" name="url" id="url" size="70" value="<?= _htmlspecialchars($url) ?>"></span>
        </p>

        <p>
        <label for="url">related debate/speech URL:</label>
        <span class="formw"><input type="text" name="related1" id="related1" size="70" value="<?= _htmlspecialchars($related1) ?>"></span>
        </p>

        <p>
        <label for="url">related debate/speech URL:</label>
        <span class="formw"><input type="text" name="related2" id="related2" size="70" value="<?= _htmlspecialchars($related2) ?>"></span>
        </p>

        <p>
        <label for="url">related debate/speech URL:</label>
        <span class="formw"><input type="text" name="related3" id="related3" size="70" value="<?= _htmlspecialchars($related3) ?>"></span>
        </p>

        <p>
        <span class="formw"><input name="btnaction" type="submit" value="Preview"</span>
        </p>
    </form>

<?php
}

function preview_featured() {
    $title = get_http_var('title');
    $context = get_http_var('context');
    $url = get_http_var('url');
    $related1 = get_http_var('related1');
    $related2 = get_http_var('related2');
    $related3 = get_http_var('related3');

    $gid = $url ? get_gid_from_url($url) : null;
    $related_gid1 = $related1 ? get_gid_from_url($related1) : null;
    $related_gid2 = $related2 ? get_gid_from_url($related2) : null;
    $related_gid3 = $related3 ? get_gid_from_url($related3) : null;

    print "<h2>Preview Content</h2>";
    if ($gid !== null) {
        $h = new MySociety\TheyWorkForYou\Homepage();
        $featured = $h->getFeaturedDebate($gid, $title, $context, [ $related_gid1, $related_gid2, $related_gid3 ]);

        include INCLUDESPATH . 'easyparliament/templates/html/homepage/featured.php';
    } else {
        print "<p>A random debate from the last 7 days will be displayed</p>";
    }

    ?>
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
<p><small>(Preview for content only, not look)</small></p>
<div class="confirm-panel">
    <form action="featured.php" method="POST">
        <input type="hidden" name="title" id="featured_title" value="<?= _htmlspecialchars($title) ?>"><br>
        <input type="hidden" name="context" id="featured_context" value="<?= _htmlspecialchars($context) ?>"><br>
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
    $gid = null;
    $parts = parse_url($url);
    parse_str($parts['query'], $query);

    if ($query['id']) {
        if (strpos($parts['path'], 'lords') !== false) {
            $gid = 'uk.org.publicwhip/lords/';
        } elseif (strpos($parts['path'], 'whall') !== false) {
            $gid = 'uk.org.publicwhip/westminhall/';
        } else {
            $gid = 'uk.org.publicwhip/debate/';
        }
        $gid .= $query['id'];
    }
    return $gid;
}

function update_featured() {
    $featured = new MySociety\TheyWorkForYou\Model\Featured();

    $title = get_http_var('title');
    $context = get_http_var('context');
    $gid = get_http_var('gid');
    $related1 = get_http_var('related_gid1');
    $related2 = get_http_var('related_gid2');
    $related3 = get_http_var('related_gid3');

    $featured->set_title($title);
    $featured->set_context($context);
    $featured->set_gid($gid);
    $featured->set_related([$related1, $related2, $related3]);

    $out = "<h4>update successful</h4>";
    $out .= "<p>Title set to " . _htmlspecialchars($title) . " and gid to " . _htmlspecialchars($gid) . "</p>";

    return $out;
}

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'		=> 'html',
        'content'	=> $menu,
    ],
]);

$PAGE->page_end();
