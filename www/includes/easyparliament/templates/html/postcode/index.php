<div class="full-page">
<div class="full-page__row search-page">

<?php
/** @var string $pc */
/** @var string $change_url */
/** @var array $sections */

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
    (<a href="<?= $change_url ?>"><?= gettext('Change postcode') ?></a>)
</p>

<nav class="rep-toc" aria-label="<?= gettext('Jump to section') ?>">
    <ul>
        <?php foreach ($sections as $section) { ?>
            <li><a href="#<?= $section['id'] ?>"><?= $section['title'] ?></a></li>
        <?php } ?>
    </ul>
</nav>

<?php foreach ($sections as $section) { ?>
<div id="<?= $section['id'] ?>">
    <h2><?= $section['title'] ?></h2>

    <?php if (!empty($section['description'])) { ?>
        <p><?= $section['description'] ?></p>
    <?php } ?>

    <?php if (!empty($section['empty_message'])) { ?>
        <p><?= $section['empty_message'] ?></p>
    <?php } ?>

    <?php foreach ($section['groups'] ?? [] as $group) { ?>
        <?php if (!empty($group['title'])) { ?>
            <h3><?= $group['title'] ?></h3>
        <?php } ?>

        <?php foreach ($group['members'] as $rep) { ?>
            <?php include "_rep_card.php"; ?>
        <?php } ?>
    <?php } ?>

    <?php if (!empty($section['footer'])) { ?>
        <p><?= $section['footer'] ?></p>
    <?php } ?>
</div>
<?php } ?>

    </div>

    <div class="search-page__section__secondary search-page-sidebar">
        <?php include dirname(__FILE__) . '/../announcements/_sidebar_right_announcements.php'; ?>
    </div>
</div>

</div>
</div>
