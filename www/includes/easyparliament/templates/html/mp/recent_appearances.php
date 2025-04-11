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
                        <li class="active"><a href="#recent_appearances"><?= gettext('Recent Appearances') ?></a></li>
                    </ul>
                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>
            <div class="primary-content__unit">
                <div class="panel">
                    <h2><?=gettext('Recent appearances') ?></h2>
                    <?php if (count($recent_appearances['appearances']) > 0): ?>
                        <ul class="appearances">
                        <?php foreach ($recent_appearances['appearances'] as $recent_appearance): ?>
                            <li>
                                <h4><a href="<?= $recent_appearance['listurl'] ?>"><?= $recent_appearance['parent']['body'] ?></a> <span class="date"><?= date('j M Y', strtotime($recent_appearance['hdate'])) ?></span></h4>
                                <blockquote><?= $recent_appearance['extract'] ?></blockquote>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                        <p><a href="<?= $recent_appearances['more_href'] ?>"><?= $recent_appearances['more_text'] ?></a></p>
                        <?php if (isset($recent_appearances['additional_links'])): ?>
                        <?= $recent_appearances['additional_links'] ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><?=gettext('No recent appearances to display.') ?></p>
                    <?php endif; ?>
                </div>
                <?php include('_profile_footer.php'); ?>
            </div>
        </div>
    </div>
</div>