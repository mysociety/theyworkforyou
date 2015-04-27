<?php

include_once '../../includes/easyparliament/init.php';

$this_page = 'admin_banner';

$db = new ParlDB;
$banner = new MySociety\TheyWorkForYou\Model\Banner;

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
    $('#preview').on('click', function bannerPreview() {
        var text = $('#banner_text').val();
        if ( text ) {
            var banner_el = $('#surveyPromoBanner');
            var fadeDelay = 1000;
            if ( !banner_el.length ) {
                fadeDelay = 0;
                banner_el = $('<div id="surveyPromoBanner" style="clear:both;padding:1em;margin-top:24px;background:#DDD;">&nbsp;</div>');
                $('body').prepend(banner_el);
            }
            banner_el.fadeOut(fadeDelay, function updateBannerText() {
                banner_el.html($('#banner_text').val());
                banner_el.fadeIn(1000);
            });
        } else {
            banner_el = $('#surveyPromoBanner').remove();
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
    $out .= '<p><label for="banner">Contents (HTML permitted)</label><br>';
    $out .= '<textarea id="banner_text" name="banner" rows="5" cols="80">' . htmlentities($text) . "</textarea></p>\n";
    $out .= '<span class="formw"><input type="button" id="preview" value="Preview"> <input name="btnaction" type="submit" value="Save"></span>';
    $out .= '</form>';

    return $out;
}

function update_banner() {
    global $banner;
    $banner_text = get_http_var('banner');

    if ( $banner->set_text($banner_text) ) {
        $out = "<h4>update successful</h4>";
        $out .= "<p>Banner text is now:</p><p>$banner_text</p>";
    } else {
        $out = "<h4>Failed to update banner text</h4>";
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
