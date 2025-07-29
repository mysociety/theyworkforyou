<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";

// if this is set to a year for which we have WTT responsiveness stats then
// it'll display a banner with the MPs stats, assuming we have them for the
// year
$display_wtt_stats_banner = '2015';
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
                        <li><a href="#profile"><?= gettext('Profile') ?></a></li>
                        <?php if (count($recent_appearances['appearances'])): ?>
                          <li><a href="#appearances"><?= gettext('Appearances') ?></a></li>
                        <?php endif; ?>
                      </ul>
                      <?php include '_featured_content.php'; ?>
                      <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <div class="panel">
                    <a name="profile"></a>
                    <h2><?=gettext('Interests') ?></h2>


                <?php include('_profile_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
