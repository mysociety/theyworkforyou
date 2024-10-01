<?php include dirname(__FILE__) . '/../homepage/search-box.php'; ?>

    <div class="full-page__row">
        <div class="homepage-panels">

            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-featured-content homepage-content-section">
                        <?php if ($featured) {
                            include dirname(__FILE__) . "/../homepage/featured.php";
                        } ?>
                    </div>
                    <div class="homepage-create-alert homepage-content-section">
                        <h2><?= gettext('Create an alert') ?></h2>
                        <h3 class="create-alert__heading"><?= gettext('Stay informed!') ?></h3>
                        <p><?= gettext('Get an email every time an issue you care about is mentioned in the Senedd (and more)') ?></p>
                        <a href="<?= $urls['alert'] ?>" class="button create-alert__button button--violet"><?= gettext('Create an alert') ?> &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-recently homepage-content-section">
                        <h2><?= gettext('Recently in the Senedd') ?></h2>
                        <ul class="recently__list"><?php
                            foreach ($debates['recent'] as $recent) {
                                include dirname(__FILE__) . '/../homepage/recent-debates.php';
                            }
?></ul>
                    </div>
                    <div class="homepage-upcoming homepage-content-section">
                        <h2><?= gettext('What is the Senedd?') ?></h2>
                        <?php include dirname(__FILE__) . '/../section/_senedd_desc.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
