<?php

include_once '../../includes/easyparliament/init.php';

$this_page = 'admin_banner';

$db = new ParlDB;
$banner = new MySociety\TheyWorkForYou\Model\AnnoucementManagement;

$PAGE->page_start();
$PAGE->stripe_start();

$out = '';
if (get_http_var('action') === 'Save') {
    $out = update_banner();
}

$out .= edit_banner_form();

print '<div id="adminbody">';
print $out;
?>
<script>
    document.getElementById('preview').addEventListener('click', function(e) {
        var text = document.getElementById('banner_text').value;
        var banner_el = document.querySelector('.banner__content');
        if ( text ) {
            if ( !banner_el ) {
                var body = document.body;
                banner_el1 = document.createElement('div');
                banner_el1.classList.add('banner');
                var banner_el2 = document.createElement('div');
                banner_el2.classList.add('full-page__row');
                var banner_el = document.createElement('div');
                banner_el.classList.add('banner__content');
                banner_el2.appendChild(banner_el);
                banner_el1.appendChild(banner_el2);
                body.insertBefore(banner_el1, body.firstChild);
            }
            banner_el.innerHTML = text;
        } else {
            banner_el.parentNode.removeChild(banner_el);
        }
    });
</script>
<?php
print '</div>';

function edit_banner_form() {
    global $banner;
    $text = $banner->get_text();

    $out = '<form action="banner.php" method="post">';
    $out .= '<input name="action" type="hidden" value="Save">';
    $out .= '<p><label for="banner">JSON input for annoucements and sidebars.<br>';
    $out .= '<span><a href="">See example of format</a>, <a href="https://jsonformatter.curiousconcept.com/">Link to online JSON validator</a></span><br>';
    $out .= '<textarea id="banner_text" name="banner" rows="30" cols="80">' . htmlentities($text) . "</textarea></p>\n";
    $out .= '<span class="formw"><input name="btnaction" type="submit" value="Save"></span>';
    $out .= '</form>';

    return $out;
}

function update_banner() {
    global $banner;
    $banner_text = get_http_var('banner');

    if ( $banner->set_text($banner_text) ) {
        $out = "<h4>update successful</h4>";
        $out .= "<p>Banner json is now:</p><p>$banner_text</p>";
    } else {
        $out = "<h4>Failed to update banner text - possibly invalid json</h4>";
    }

    return $out;
}

$menu = $PAGE->admin_menu();

$PAGE->stripe_end(array(
    array(
        'type'    => 'html',
        'content' => $menu
    )
));

$PAGE->page_end();
