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
                        <li><a href="https://pages.mysociety.org/parl_register_interests/datasets/parliament_2024/latest">Download a spreadsheet</a></li>
                        <li><a href="https://www.mysociety.org/2024/01/17/improving-the-register-of-mps-interests/">Read more about the data</a></li>
                    </ul>

                    <?php foreach ($register_interests['chamber_registers'] as $register): ?>
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
                    <?php endforeach; ?>

                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <?php if ($register_interests): ?>

                    <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Person $register */ ?>
                    <?php foreach ($register_interests['chamber_registers'] as $chamber => $register): ?>
                        
                    <div class="panel register">
                    <a name="register"></a>
                    <h2>Register of Members&rsquo; Interests - <?= $register->displayChamber() ?></h2>

                    <p>
                        <a href="<?= WEBPATH ?>regmem/?p=<?= $person_id ?>&chamber=<?= $chamber ?>">View the history of this person's entries in the Register</a>
                    </p>

                        <p>This register last updated on: <?= $register->published_date ?></p>

                        <?php foreach ($register->categories as $category): ?>
                            <?php if ($category->only_null_entries()): ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <h3 id="category-<?= $register->chamber . $category->category_id ?>"><?= $category->category_name ?></h3>
                        
                            <?php foreach ($category->entries as $entry): ?>
                                <?php if ($entry->null_entry == false): ?>
                                    <?php include('_register_entry.php'); ?>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        
                        <?php endforeach; ?>
                    
                        </div>
                    <?php endforeach; ?>

                <div class="panel">
                    <p>
                         <a class="moreinfo-link" href="https://www.parliament.uk/mps-lords-and-offices/standards-and-financial-interests/parliamentary-commissioner-for-standards/registers-of-interests/register-of-members-financial-interests/">More about the register</a>
                    </p>
                </div>
                <?php endif; ?>

                <?php include('_profile_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
