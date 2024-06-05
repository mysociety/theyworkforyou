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
                      <li><a href="https://www.mysociety.org/2024/01/17/improving-the-register-of-mps-interests/">Download a spreadsheet</a></li>
                      <?php include '_featured_content.php'; ?>
                      <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <?php if ($register_interests): ?>
                <div class="panel register">
                    <a name="register"></a>
                    <h2>Register of Members&rsquo; Interests</h2>
                    <p><b>New</b>: <a href="https://www.mysociety.org/2024/01/17/improving-the-register-of-mps-interests/">Download a spreadsheet of all Members Interests.</a></p>

                    <p>
                        <a href="<?= WEBPATH ?>regmem/?p=<?= $person_id ?>">View the history of this MP&rsquo;s entries in the Register</a>
                    </p>
                    <?php if ($register_interests['date']): ?>
                        <p>Last updated: <?= $register_interests['date'] ?>.</p>
                    <?php endif; ?>

                    <?= $register_interests['data'] ?>


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
