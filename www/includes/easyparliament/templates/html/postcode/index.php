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

<?php foreach ($sections as $section) {
    // Count total members across all groups in this section
    $total_members = 0;
    foreach ($section['groups'] ?? [] as $group) {
        $total_members += count($group['members']);
    }
    ?>
<div id="<?= $section['id'] ?>">
    <h2><?= $section['title'] ?></h2>

    <?php if (!empty($section['description'])) { ?>
        <p><?= $section['description'] ?></p>
    <?php } ?>

    <?php if (!empty($section['council_names'])) { ?>
        <?php
                $bold_names = array_map(fn($name) => '<strong>' . htmlspecialchars($name) . '</strong>', $section['council_names']);
        $names_html = implode(' and ', $bold_names);
        ?>
        <?php if (count($section['council_names']) === 1) { ?>
            <p><?= sprintf(gettext('Your local council is %s.'), $names_html) ?></p>
        <?php } else { ?>
            <p><?= sprintf(gettext('Your local councils are %s.'), $names_html) ?></p>
        <?php } ?>
    <?php } ?>

    <?php if (!empty($section['writetothem_url'])) { ?>
        <p><?= sprintf(gettext('Find your local councillors and write to any of your representatives through <a href="%s">WriteToThem.com</a>.'), $section['writetothem_url']) ?></p>
    <?php } ?>

    <?php if (!empty($section['empty_message'])) { ?>
        <p><?= $section['empty_message'] ?></p>
    <?php } ?>

    <?php if ($total_members > 1) { ?>
        <?php $toggle_id = 'expand-toggle-' . $section['id']; ?>
        <button id="<?= $toggle_id ?>" style="display:none"><?= gettext('Expand all') ?></button>
    <?php } ?>

    <?php foreach ($section['groups'] ?? [] as $group) { ?>
        <?php if (!empty($group['title'])) { ?>
            <h3><?= $group['title'] ?></h3>
        <?php } ?>

        <?php foreach ($group['members'] as $rep) { ?>
            <?php include "_rep_card.php"; ?>
        <?php } ?>
    <?php } ?>

    <?php if ($total_members > 1) { ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            initDetailsToggle({
                buttonId: <?= json_encode($toggle_id) ?>,
                selector: '#<?= $section['id'] ?> details.rep-detail',
                expandLabel: <?= json_encode(gettext('Expand all')) ?>,
                collapseLabel: <?= json_encode(gettext('Collapse all')) ?>,
                autoExpand: new URLSearchParams(window.location.search).get('expand') === '1'
            });
        });
        </script>
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
