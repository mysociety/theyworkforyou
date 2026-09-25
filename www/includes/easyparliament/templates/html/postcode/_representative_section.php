<?php

use MySociety\TheyWorkForYou\DataClass\Postcode\RepresentativeSectionData;

/** @var RepresentativeSectionData $section */
/** @var bool $expand */

// Count total members across all groups in this section
$total_members = 0;
foreach ($section->groups as $group) {
    $total_members += count($group->members);
}
?>

    <?php include "_representative_description.php"; ?>

    <?php if (!$section->data_available) { ?>
        <p><?= $section->empty_message ?></p>
        <?php return;
    } ?>

    <?php if ($total_members > 1) { ?>
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

    <?php if ($total_members > 1) { ?>
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
