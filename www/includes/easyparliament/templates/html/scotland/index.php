<?php include dirname(__FILE__) . '/../homepage/search-box.php'; ?>

    <div class="full-page__row">
        <div class="homepage-panels">
            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-featured-content homepage-content-section">
                        <?php if ( $featured ) {
                             include dirname(__FILE__) . "/../homepage/featured.php";
                        } ?>
                    </div>
                    <div class="homepage-create-alert homepage-content-section">
                        <h2>Create an alert</h2>
                        <h3 class="create-alert__heading">Stay informed!</h3>
                        <p>Get an email every time an issue you care about is mentioned in the Scottish Parliament (and more)</p>
                        <a href="<?= $urls['alert'] ?>" class="button create-alert__button button--violet">Create an alert &rarr;</a>
                    </div>
                </div>
            </div>
            <div class="panel panel--flushtop clearfix">
                <div class="row nested-row">
                    <div class="homepage-recently homepage-content-section">
                        <?php include dirname(__FILE__) . '/../homepage/recent-votes.php'; ?>

                        <h2>Recently in Parliament</h2>
                        <ul class="recently__list"><?php
                            foreach ( $debates['recent'] as $recent ) {
                                include dirname(__FILE__) . '/../homepage/recent-debates.php';
                            }
                        ?></ul>
                    </div>
                    <div class="homepage-upcoming homepage-content-section">
                        <h2>What is the Scottish Parliament?</h2>
                        <p>The Scottish Parliament is the national legislature of Scotland, located in
                        Edinburgh. The Parliament consists of 129 members known as <a
                        href="/msps/">MSPs</a>, elected for four-year terms; 73 represent
                        individual geographical constituencies, and 56 are regional representatives.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
