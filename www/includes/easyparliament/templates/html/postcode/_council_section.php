<?php

use MySociety\TheyWorkForYou\DataClass\Postcode\CouncilSectionData;

/** @var CouncilSectionData $section */
?>
    <?php if (!empty($section->council_names)) { ?>
        <?php
        $bold_names = array_map(fn($name) => '<strong>' . htmlspecialchars($name) . '</strong>', $section->council_names);
        $names_html = implode(' and ', $bold_names);
        ?>
        <?php if (count($section->council_names) === 1) { ?>
            <p><?= sprintf(gettext('Your local council is %s.'), $names_html) ?></p>
        <?php } else { ?>
            <p><?= sprintf(gettext('Your local councils are %s.'), $names_html) ?></p>
        <?php } ?>
    <?php } ?>

        <p><?= sprintf(gettext('Find your local councillors and write to any of your representatives through <a href="%s">WriteToThem.com</a>.'), $section->writetothem_url) ?></p>
