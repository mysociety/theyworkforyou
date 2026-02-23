<?php if (isset($error)) { ?>
    <div class="full-page static-page toc-page">
        <div class="full-page__row">
            <div class="toc-page__col">
                <div class="panel">
                <?= $error ?>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>

<?php $just_new_suffix = !empty($just_new) ? '&just_new=true' : ''; ?>
<?php if (!empty($just_new)) { ?>
<style>
    .old_entry {
        display: none;
    }
</style>
<?php } ?>

<div class="full-page static-page toc-page">
    <div class="full-page__row">

        <div class="toc-page__col">
        <?php if ($selected_category_id) { ?>
        <div class="toc js-toc">
            <ul>
            <li><a class="js-just-new-link" href="?chamber=<?= $chamber_slug ?><?= $just_new_suffix ?>"><?= gettext("Back to categories list")?></a></li>

                <?php foreach ($categories as $category_id => $category_name) { ?>
                    <li><a class="js-just-new-link" href="?chamber=<?= $chamber_slug ?>&category_id=<?= $category_id ?><?= $just_new_suffix ?>"><?= $category_name ?></a></li>
                <?php }; ?>
            </ul>
        </div>
        <?php }; ?>

        </div>
        <div class="toc-page__col">

            <div class="panel">
                <h1>📒<?= gettext('Register of interests')?></h1>
                <?php $register_heading_base = $register->displayChamber() . ' - ' . $register->published_date; ?>
                <h2 id="register-date-heading" data-base-text="<?= htmlspecialchars($register_heading_base) ?>" data-just-new-label="<?= htmlspecialchars(gettext('just new entries')) ?>"><?= htmlspecialchars($register_heading_base) ?><?php if (!empty($just_new)) { ?> (<?= gettext('just new entries') ?>)<?php } ?></h2>
                <p><?= gettext('This page shows the latest version of the register of interests by category.') ?></p>
                <?php if (LANGUAGE == 'cy') { ?>
                    <p><?= gettext('For more information, see the official Senedd page') ?></a>.
                <?php } else { ?>
                    <p>For more information on the different categories, see the <a href="<?= $register->officialUrl() ?>">the official <?= $register->displayChamber() ?> page</a>.
                <?php } ?>
                <p><a href="/interests/"><?= gettext('Read more about our registers data')?></a>.</p>
                <hr>
                <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Register $register */ ?>

                <?php if ($selected_category_id === null) { ?>
                    <h3><?= gettext('Categories') ?></h3>
                    <ul>
                        <?php foreach ($categories as $category_id => $category_name) { ?>
                            <li><a class="js-just-new-link" href="?chamber=<?= $chamber_slug ?>&category_id=<?= $category_id ?><?= $just_new_suffix ?>"><?= $category_name ?></a></li>
                        <?php }; ?>
                    </ul>
                <?php } else { ?>

                    <h2 id="category-<?= $category_id ?>"><?= $category_emojis[$selected_category_id] ?><?= $selected_category_name ?></h2>
                    <?php include INCLUDESPATH . 'easyparliament/templates/html/register/_toggle_buttons.php'; ?>
                <?php } ; ?>

                    <?php foreach ($register->persons as $person) { ?>
                        <?php foreach ($person->categories as $person_category) { ?>
                        <?php if ($person_category->category_id != $selected_category_id || $person_category->only_null_entries()) {
                            continue;
                        }; ?> 
                        <div class="person-items <?php if ($person_category->only_old_entries($register->published_date)) { ?>old_entry<?php } ?>">
                        <h3><a href="/mp/<?= $person->intId() ?>/register"><?= $person->person_name ?></a></h3>
                        <?php foreach ($person_category->entries as $entry) { ?>
                                <?php include INCLUDESPATH . 'easyparliament/templates/html/register/_entry_display.php'; ?>
                        <?php }; ?>
                        <hr/>
                        </div>
                        <?php }; ?>
                    <?php }; ?>

            </div>
        </div>  

    </div>
</div>

<?php } ?>
