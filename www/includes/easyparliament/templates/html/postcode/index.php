<div class="full-page">
<div class="full-page__row search-page">

<?php

use MySociety\TheyWorkForYou\DataClass\Postcode\SectionData;
use MySociety\TheyWorkForYou\DataClass\Postcode\RepresentativeSectionData;

/** @var string $pc */
/** @var string $change_postcode_url */
/** @var list<SectionData> $sections */

// Include Scottish Parliament election template if there are SP ballots
if (!empty($sp_ballots)) {
    include "sp2026.php";
}

// Include Welsh Senedd election template if there is a Senedd ballot
if (!empty($senedd_ballot)) {
    include "senedd2026.php";
}
?>

<div class="search-page__section">
    <div class="search-page__section__primary">

<h1><?= gettext('Your representatives') ?></h1>
<p><?= sprintf(gettext('Based on postcode <strong>%s</strong>'), $pc) ?>
    (<a href="<?= $change_postcode_url ?>"><?= gettext('Change postcode') ?></a>)
</p>

<nav class="rep-toc" aria-label="<?= gettext('Jump to section') ?>">
    <ul>
        <?php foreach ($sections as $section) { ?>
            <li><a href="#<?= $section->id->value ?>"><?= $section->title ?></a></li>
        <?php } ?>
    </ul>
</nav>

<?php foreach ($sections as $section) { ?>
<div id="<?= $section->id->value ?>">
    <h2><?= $section->title ?></h2>

    <?php
    switch (true) {
        case $section instanceof RepresentativeSectionData:
            include "_representative_section.php";
            break;
    }
    ?>
</div>
<?php } ?>

    </div>

    <div class="search-page__section__secondary search-page-sidebar">
        <?php include dirname(__FILE__) . '/../announcements/_sidebar_right_announcements.php'; ?>
    </div>
</div>

</div>
</div>
