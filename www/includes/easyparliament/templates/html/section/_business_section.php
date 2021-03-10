    <div class="business-section">
        <div class="business-section__header">
            <h1 class="business-section__header__title">
            Recent <?= $title ?>
            </h1>
        </div>

        <?php
            if ( isset($content['data']['data']) ) {
                $data = $content['data']['data'];
                include '_business_list.php'; ?>
                <div class="business-section__secondary">
                    <div class="business-section__secondary__item">
                        <h3>What is this?</h3>
                        <?php include '_' . $section . '_desc.php'; ?>
                    </div>
                    <div class="business-section__secondary__item">
                        <?php
                        $calendar = $content['calendar']['years'];
                        include '_calendar_section.php';
                        ?>
                    </div>
                  <?php if ( !isset($no_survey) ) { ?>
                    <div class="business-section__secondary__item">
                        <?php include( dirname(__FILE__) . '/../sidebar/looking_for.php' ); ?>
                    </div>
                  <?php } ?>
                  <?php if ( isset($content['rssurl']) ) { ?>
                    <div class="business-section__secondary__item">
                        <p class="rss-feed">
                            <a href="<?= WEBPATH . $content['rssurl'] ?>">RSS feed of <?= $title ?></a>
                        </p>
                    </div>
                  <?php } ?>
                </div>
            <?php } else { ?>
             <div class="business-section__primary">
                None.
            </div>
            <?php } ?>
    </div>
