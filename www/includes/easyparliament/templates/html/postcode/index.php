<div class="full-page static-page legacy-page">
<div class="full-page__row">
<div class="panel">
    <div class="stripe-side">
        <div class="main">
<?php

include "ge2024.php";

# The below is normally the main column, but for now let us make it the sidebar...

?>

        </div>
        <div class="sidebar">
            <div class="block" id="current">

    <h2><?= gettext('Your representatives') ?></h2>
    <ul>
        <li>
            <?php if ($mp['former']) {
                printf(gettext('Your former <strong>MP</strong> (Member of Parliament) is <a href="%s">%s</a>, %s'), '/mp/?p=' . $mp['person_id'], $mp['name'], gettext($mp['constituency']));
            } else {
                printf(gettext('Your <strong>MP</strong> (Member of Parliament) is <a href="%s">%s</a>, %s'), '/mp/?p=' . $mp['person_id'], $mp['name'], gettext($mp['constituency']));
            } ?>
        </li>

<?php
    if (isset($mcon)) {
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
            echo '<li><a href="' . $urlp . $reg['person_id'] . '">';
            echo $reg['given_name'] . ' ' . $reg['family_name'];
            echo '</a>';
        }
        echo '</ul>';
    }
    echo '</ul>';

include("repexplain.php");

?>
                <h3><?= gettext('Browse people') ?></h3>
                <ul>
                    <li><a href="<?= $MPSURL->generate() ?>"><?= gettext('Browse all MPs') ?></a></li>
                  <?php if (isset($REGURL)) { ?>
                    <li><a href="<?= $REGURL->generate() ?>"><?= $browse_text ?></a></li>
                  <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</div>
</div>
</div>
