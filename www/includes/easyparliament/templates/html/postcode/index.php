<style>
.postcode-rep-list__item, .postcode-rep-list__sub-item {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}
.postcode-rep-list__sub-item {
    align-items: baseline;
}
.postcode-rep-list__link {
    flex-shrink: 0;
    margin-bottom: 0.5em;
    margin-right: 0.5em;
}
</style>

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
    return $image;
}

function member_image_box(string $person_id, string $person_url, string $person_name): void {
    // Render a small image box for a person with a link to the person page
    // If image_url is null, render nothing
    $image_url = image_from_person_id($person_id);
    if ($image_url) {
        echo '<a class="postcode-rep-list__link" href="' . $person_url . '"><img src="' . $image_url . '" height=80 width=60 alt="' . $person_name .'"></a>';
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
        <li class="postcode-rep-list__item"><span>
            <?php if ($mp['former']) {
                printf(gettext('Your former <strong>MP</strong> (Member of Parliament) is <a href="%s">%s</a>, %s'), '/mp/?p=' . $mp['person_id'], $mp['name'], gettext($mp['constituency']));
            } else {
                printf(gettext('Your <strong>MP</strong> (Member of Parliament) is <a href="%s">%s</a>, %s'), '/mp/?p=' . $mp['person_id'], $mp['name'], gettext($mp['constituency']));
            } ?>.
            <?php if ($mp['standing_down_2024']) {
                echo 'They are standing down at the general election.';
            } ?>
            </span>
            <?php member_image_box($mp["person_id"], '/mp/?p=' . $mp['person_id'],  $mp['name']) ?>
        </li>

<?php
    if (isset($mcon) && !empty($mcon)) {
        $name = $mcon['given_name'] . ' ' . $mcon['family_name'];
        echo '<li class="postcode-rep-list__item"><span>';
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
        echo '</span>';
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
            echo '<li><span class="postcode-rep-list__sub-item"><a href="' . $url . '">' . $name  . '</a>';
            member_image_box($reg["person_id"], $url, $name );
            echo '</span></li>';
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

<h3>We can make politics better together</h3>

<p>We want MPs to meet the standards and expectations of the people who elected them - <strong>you</strong>!</p>
<p>Learn about <a href="/support-us/?utm_source=theyworkforyou.com&utm_content=postcode+donate&utm_medium=link&utm_campaign=postcode&how-much=5">our current work</a>, and <a href="https://www.mysociety.org/democracy/who-funds-them/">our new project WhoFundsThem</a> - looking into MPs' and APPGs' financial interests.</p>
<a href="/support-us/?utm_source=theyworkforyou.com&utm_content=postcode+donate&utm_medium=link&utm_campaign=postcode&how-much=5#donate-form" class="button" style="width:100%">Donate £5 to TheyWorkForYou</a>
<a href="https://www.mysociety.org/democracy/who-funds-them/" class="button" style="width:100%">Support our WhoFundsThem campaign</a>
            </div>
        </div>
    </div>
</div>
</div>
</div>
