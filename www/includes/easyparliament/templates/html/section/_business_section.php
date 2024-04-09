    <div class="business-section">
        <div class="business-section__header">
            <h1 class="business-section__header__title">
            <?php if ($title == "Dadleuon y Senedd"){ ?>
            Dadleuon diweddar Y Senedd
            <?php } else { ?>
            <?= sprintf(gettext('Recent %s'), $title) ?>
            <?php } ?>
            </h1>
        </div>

        <?php
            if ( isset($content['data']['data']) ) {
                $data = $content['data']['data'];
                include '_business_list.php'; ?>
                <div class="business-section__secondary">
                    <div class="business-section__secondary__item">
                        <h3><?= gettext('What is this?') ?></h3>
                        <?php include '_' . $section . '_desc.php'; ?>
                    </div>
                    <div class="business-section__secondary__item">
                        <?php
                        $calendar = $content['calendar']['years'];
                        include '_calendar_section.php';
                        ?>
                    </div>
                  <?php if ( isset($content['rssurl']) ) { ?>
                    <div class="business-section__secondary__item">
                        <p class="rss-feed">
                            <a href="<?= WEBPATH . $content['rssurl'] ?>"><?= sprintf(gettext('RSS feed of %s'), $title) ?></a>
                        </p>
                        <?php include dirname(__FILE__) . '/../announcements/_sidebar_right_announcements.php'; ?>
                    </div>
                  <?php } ?>
                </div>
            <?php } else { ?>
             <div class="business-section__primary">
                None.
            </div>
            <?php } ?>
    </div>
