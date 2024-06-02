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

                <div class="panel panel--secondary">
                    <p><?= gettext('Note for journalists and researchers: The data on this page may be used freely, on condition that TheyWorkForYou.com is cited as the source.') ?></p>

                    <p><?php print gettext('This data was produced by TheyWorkForYou from a variety of sources.') . ' ';
                        printf(gettext('Voting information from <a href="%s">Public Whip</a>.'), "https://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/ $member_id&amp;showall=yes"); ?></p>

                  <?php if($image && $image['exists']) {
                      if(isset($data['photo_attribution_text'])) { ?>
                    <p>
                      <?php if(isset($data['photo_attribution_link'])) { ?>
                        <?= gettext('Profile photo:') ?>
                        <a href="<?= $data['photo_attribution_link'] ?>"><?= $data['photo_attribution_text'] ?></a>
                      <?php } else { ?>
                        <?= gettext('Profile photo:') ?> <?= $data['photo_attribution_text'] ?>
                      <?php } ?>
                    </p>
                  <?php }
                  } else { ?>
                    <p>
                        We&rsquo;re missing a photo of <?= $full_name ?>.
                        If you have a photo <em>that you can release under
                        a Creative Commons Attribution-ShareAlike license</em>
                        or can locate a <em>copyright free</em> photo,
                        <a href="mailto:<?= str_replace('@', '&#64;', CONTACTEMAIL) ?>">please email it to us</a>.
                        Please do not email us about copyrighted photos
                        elsewhere on the internet; we can&rsquo;t use them.
                    </p>
                  <?php } ?>
                </div>

            </div>
        </div>
    </div>
</div>
