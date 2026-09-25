<?php

use MySociety\TheyWorkForYou\DataClass\Postcode\RepresentativeSectionData;
use MySociety\TheyWorkForYou\HouseType;

/** @var RepresentativeSectionData $section */
/** @var bool $expand */

// Count members with expandable details across all groups in this section
$members_with_details = 0;
foreach ($section->groups as $group) {
    foreach ($group->members as $member) {
        $members_with_details += $member->hasDetails() ? 1 : 0;
    }
}
?>

    <p><?= match ($section->house) {
        HouseType::COMMONS => gettext('Your MP represents you in the House of Commons. The House of Commons is responsible for making laws in the UK and for overall scrutiny of all aspects of government.'),
        HouseType::SCOTLAND => gettext('Your MSPs represent you in the Scottish Parliament. The Scottish Parliament is responsible for a wide range of devolved matters in which it sets policy independently of the London Parliament. Devolved matters include education, health, agriculture, justice and prisons. It also has some tax-raising powers.'),
        HouseType::NI => gettext('Your MLAs represent you in the Northern Ireland Assembly. The Northern Ireland Assembly has full authority over "transferred matters", which include agriculture, education, employment, the environment and health.'),
        HouseType::WALES => gettext('Your MSs represent you in the Senedd. The Senedd has a wide range of powers over areas including economic development, transport, finance, local government, health, housing and the Welsh Language.'),
    } ?></p>

    <?php if ($section->empty_message !== null) { ?>
        <p><?= $section->empty_message ?></p>
        <?php return; ?>
    <?php } ?>

    <?php if ($members_with_details > 1) { ?>
        <?php $toggle_id = 'expand-toggle-' . $section->id->value; ?>
        <button id="<?= $toggle_id ?>" style="display:none"><?= gettext('Expand all') ?></button>
    <?php } ?>

    <?php foreach ($section->groups as $group) { ?>
        <?php if (!empty($group->title)) { ?>
            <h3><?= $group->title ?></h3>
        <?php } ?>

        <?php foreach ($group->members as $rep) { ?>
            <?php include "_rep_card.php"; ?>
        <?php } ?>
    <?php } ?>

    <?php if ($members_with_details > 1) { ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            initDetailsToggle({
                buttonId: <?= json_encode($toggle_id) ?>,
                selector: '#<?= $section->id->value ?> details.rep-detail',
                expandLabel: <?= json_encode(gettext('Expand all')) ?>,
                collapseLabel: <?= json_encode(gettext('Collapse all')) ?>,
                autoExpand: new URLSearchParams(window.location.search).get('expand') === '1'
            });
        });
        </script>
    <?php } ?>

    <?php if (!empty($section->footer)) { ?>
        <p><?= $section->footer ?></p>
    <?php } ?>
