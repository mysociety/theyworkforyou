<div class="full-page static-page legacy-page">
<div class="full-page__row">
<div class="panel">
    <div class="stripe-side">
        <div class="main">
<?php

function image_from_person_id(int $person_id): ?string {
    // Use utility method rather than member object to avoid loading full object
    // Using the same image and not using the placeholder
    [$image, $size] = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($person_id, true, false);
    return $size !== null ? $image : null;
}

function member_image_box(string $person_id, string $person_url, string $person_name): void {
    // Render a small image box for a person with a link to the person page
    // If image_url is null, render nothing
    $image_url = image_from_person_id($person_id);
    if ($image_url) {
        echo '<div class="postcode-mp-image-wrapper">';
        echo '<a href="' . $person_url . '"><img src="' . $image_url . '" height=80 width=60 alt="' . $person_name .'"></a>';
        echo '</div>';
    }
}

include "ge2024.php";

# The below is normally the main column, but for now let us make it the sidebar...


?>

        </div>
        <div class="sidebar">
            <div class="block">

<div id="current">
    <h2><?= gettext('Your representatives') ?></h2>
    <ul>
        <li>
            <?php if ($mp['former']) {
                printf(gettext('Your former <strong>MP</strong> (Member of Parliament) is <a href="%s">%s</a>, %s'), '/mp/?p=' . $mp['person_id'], $mp['name'], gettext($mp['constituency']));
            } else {
                printf(gettext('Your <strong>MP</strong> (Member of Parliament) is <a href="%s">%s</a>, %s'), '/mp/?p=' . $mp['person_id'], $mp['name'], gettext($mp['constituency']));
            } ?>.
            <?php if ($mp['standing_down_2024']) {
                echo 'They are standing down at the general election.';
            } ?>
            <?php member_image_box($mp["person_id"], '/mp/?p=' . $mp['person_id'],  $mp['name']) ?>
        </li>

<?php
    if (isset($mcon) && !empty($mcon)) {
        $name = $mcon['given_name'] . ' ' . $mcon['family_name'];
        echo '<li>';
        if ($house == HOUSE_TYPE_SCOTLAND) {
            $url = $urlp . $mcon['person_id'];
            $cons = $mcon['constituency'];
            if ($current) {
                printf(gettext('Your <strong>constituency MSP</strong> (Member of the Scottish Parliament) is <a href="%s">%s</a>, %s'), $url, $name, $cons);
            } else {
                printf(gettext('Your <strong>constituency MSP</strong> (Member of the Scottish Parliament) was <a href="%s">%s</a>, %s'), $url, $name, $cons);
            }
        } elseif ($house == HOUSE_TYPE_WALES) {
            $url = $urlp . $mcon['person_id'];
            $cons = gettext($mcon['constituency']);
            if ($current) {
                # First %s is URL, second %s is name, third %s is constituency
                printf(gettext('Your <strong>constituency MS</strong> (Member of the Senedd) is <a href="%s">%s</a>, %s'), $url, $name, $cons);
            } else {
                # First %s is URL, second %s is name, third %s is constituency
                printf(gettext('Your <strong>constituency MS</strong> (Member of the Senedd) was <a href="%s">%s</a>, %s'), $url, $name, $cons);
            }
        }
        member_image_box($mcon["person_id"], $url, $name);
        echo '</li>';
    }
    if (isset($mreg)) {
        if ($current) {
            if ($house == HOUSE_TYPE_NI) {
                echo '<li>' . sprintf(gettext('Your <strong>%s MLAs</strong> (Members of the Legislative Assembly) are:'), $areas[$area_type]);
            } elseif ($house == HOUSE_TYPE_WALES){
                echo '<li>' . sprintf(gettext('Your <strong>%s region MSs</strong> are:'), gettext($areas[$area_type]));
            } else {
                echo '<li>' . sprintf(gettext('Your <strong>%s %s</strong> are:'), gettext($areas[$area_type]), $member_names['plural']);
            }
        } else {
            if ($house == HOUSE_TYPE_NI) {
                echo '<li>' . sprintf(gettext('Your <strong>%s MLAs</strong> (Members of the Legislative Assembly) were:'), $areas[$area_type]);
            } elseif ($house == HOUSE_TYPE_WALES){
                echo '<li>' . sprintf(gettext('Your <strong>%s region MSs</strong> were:'), gettext($areas[$area_type]));
            } else {
                echo '<li>' . sprintf(gettext('Your <strong>%s %s</strong> were:'), gettext($areas[$area_type]), $member_names['plural']);
            }
        }
        echo '<ul>';
        foreach ($mreg as $reg) {
            $url = $urlp . $reg['person_id'];
            $name = $reg['given_name'] . ' ' . $reg['family_name'];
            echo '<li><a href="' . $url . '">' . $name  . '</a>';
            member_image_box($reg["person_id"], $url, $name );
            echo '</li>';
        }
        echo '</ul>';
    }
    echo '</ul>';

echo '</div>';

include("repexplain.php");

?>
                <h3><?= gettext('Browse people') ?></h3>
                <ul>
                    <li><a href="<?= $MPSURL->generate() ?>"><?= gettext('Browse all MPs') ?></a></li>
                  <?php if (isset($REGURL)) { ?>
                    <li><a href="<?= $REGURL->generate() ?>"><?= $browse_text ?></a></li>
                  <?php } ?>
                </ul>

<h3><?= gettext('Donate') ?></h3>
<p>
For the election mySociety are busy helping people to understand what their
election candidates stand for with <a href="https://www.theyworkforyou.com/">TheyWorkForYou</a>; supporting people to have
informed conversations with candidates around climate using <a href="https://www.localintelligencehub.com/">Local Intelligence
Hub</a>; and ensuring all our democracy services are ready to go on day one of a
new government.

<p><a href="/support-us/?utm_source=theyworkforyou.com&amp;utm_content=postcode+donate&amp;utm_medium=link&amp;utm_campaign=postcode" class="button">Donate</a>

<p>Whoever is elected, they need to understand the importance of
transparency and accountability — and we’ll be making sure that happens.


            </div>
        </div>
    </div>
</div>
</div>
</div>
