<?php

use MySociety\TheyWorkForYou\DataClass\Postcode\RepresentativeSectionData;

/** @var RepresentativeSectionData $section */

?>

    <?php include "_representative_description.php"; ?>

    <?php if (!$section->data_available) { ?>
        <p><?= $section->empty_message ?></p>
        <?php return;
    } ?>


    <?php foreach ($section->groups as $group) { ?>
        <?php if (!empty($group->title)) { ?>
            <h3><?= $group->title ?></h3>
        <?php } ?>

        <?php foreach ($group->members as $rep) { ?>
            <?php include "_rep_card.php"; ?>
        <?php } ?>
    <?php } ?>


    <?php if (!empty($section->footer)) { ?>
        <p><?= $section->footer ?></p>
    <?php } ?>
