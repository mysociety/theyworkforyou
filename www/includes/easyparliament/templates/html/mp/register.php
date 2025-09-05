<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <?php include '_person_navigation.php'; ?>
                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                    <?php if (LANGUAGE == 'en') { ?>

                    <div class="panel register">
                        <h2>📖 Registers of Interests</h2>
                        <p>Representatives in the UK's Parliaments need to declare financial interests (such as employment, donations, and gifts) that could be considered by others to influence their judgement or actions.</p>
                        <p>As part of our WhoFundsThem project, we have written about <a href="https://research.mysociety.org/html/beyond-transparency">how the rules on financial interests can be improved</a>.</p>
                        <p>You can read more about <a href="/interests/">the different kind of data we hold about financial interests</a>, including <a href="/interests/#spreadsheets">spreadsheet downloads</a>.</p>
                        <hr/>
                        
                        <?php if (!empty($register_interests['chamber_registers'])) { ?>
                            <p><?= sprintf(gettext('%s has registers in the following chambers:'), $full_name) ?></p>
                            <ul class="register-navigation-menu">
                                <?php foreach ($register_interests['chamber_registers'] as $chamber => $register) { ?>
                                    <li>
                                        <a href="#register-<?= $chamber ?>"><?= $register->displayChamber() ?></a>
                                        <span class="register-last-updated">(<?= gettext('Last updated:') ?> <?= $register->published_date ?>)</span>
                                        <ul class="register-submenu">
                                            <?php foreach ($register->categories as $category) { ?>
                                                <?php if ($category->only_null_entries()) {
                                                    continue;
                                                }; ?>
                                                <li>
                                                    <?= $category->emoji() ?> <a href="#category-<?= $register->chamber . $category->category_id ?>"><?= $category->category_name ?></a>
                                                    
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </li>
                                <?php } ?>
                            </ul>
                        <?php } ?>

                    </div>
                    <?php } ?>

                <?php if ($register_interests) { ?>

                    <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Person $register */ ?>
                    <?php foreach ($register_interests['chamber_registers'] as $chamber => $register) { ?>


                        
                    <div class="panel register">
                    <a name="register"></a>
                    <h2 id="register-<?= $chamber ?>"><?= gettext('Register of Interests') ?> &ndash; <?= $register->displayChamber() ?></h2>

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
                            <h2 id="category-<?= $register->chamber . $category->category_id ?>"><?= $category->emoji() ?> <?= $category->category_name ?></h3>
                        
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
