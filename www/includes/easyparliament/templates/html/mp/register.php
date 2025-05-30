<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <?php include '_person_navigation.php'; ?>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>

                <h3 class="browse-content"><?= gettext('Browse content') ?></h3>
                    <ul>
                        <li><a href="/interests/"><?= gettext('Read more about the register') ?></a></li>
                        <li><a href="/interests/#spreadsheets"><?= gettext('Get this data in a spreadsheet') ?></a></li>

                    </ul>

                    <?php if ($register_interests) {
                        foreach ($register_interests['chamber_registers'] as $register) { ?>
                    <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Person $register */ ?>

                    <h3 class="browse-content"><?= $register->displayChamber() ?></h3>
                    <ul>
                            <?php foreach ($register->categories as $category): ?>
                                <?php if ($category->only_null_entries()): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <li><a href="#category-<?= $register->chamber . $category->category_id ?>"><?= $category->category_name ?></a></li>
                            <?php endforeach; ?>
                    </ul>
                    <?php }
                        } ?>

                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <?php if ($register_interests) { ?>

                    <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Person $register */ ?>
                    <?php foreach ($register_interests['chamber_registers'] as $chamber => $register) { ?>
                        
                    <div class="panel register">
                    <a name="register"></a>
                    <h2>📖 <?= gettext('Register of Interests') ?> &ndash; <?= $register->displayChamber() ?></h2>

                    <p>
                        <a href="<?= WEBPATH ?>regmem/?p=<?= $person_id ?>&chamber=<?= $chamber ?>"><?= gettext('View the history of this person’s entries in the Register') ?></a>
                    </p>

                        <p><?= gettext('This register last updated on:') ?> <?= $register->published_date ?></p>

                        
                        <?php if (LANGUAGE == 'cy') { ?>
                            <p><?= gettext('For more information, see the official Senedd page') ?></a>.
                        <?php } else { ?>
                            <p>For more information on the different categories, see <a href="<?= $register->officialUrl() ?>">the official <?= $register->displayChamber() ?> page</a>.
                        <?php } ?>

                        </p>

                        <?php foreach ($register->categories as $category) { ?>
                            <?php if ($category->only_null_entries()) {
                                continue;
                            }; ?>
                            <hr>
                            <h3 id="category-<?= $register->chamber . $category->category_id ?>"><?= $category->emoji() ?> <?= $category->category_name ?></h3>
                        
                            <?php foreach ($category->entries as $entry) {
                                if ($entry->null_entry == false) {
                                    include('_register_entry.php');
                                }
                            } ?>
                        
                        <?php }; ?>
                    
                        </div>
                    <?php }; ?>

                <?php }; ?>

                <?php include('_profile_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
