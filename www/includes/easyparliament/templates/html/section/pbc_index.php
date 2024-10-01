<div class="full-page__row">

    <div class="business-section">
        <div class="business-section__header">
            <h1 class="business-section__header__title">
                Public Bill Committees
            </h1>
            <div class="business-section__header__description">
                <p>
                Previously called Standing Committees, Public Bill Commitees
                study proposed legislation (Bills) in detail, debating each
                clause and reporting any amendments to the Commons for further debate.
                </p>

                <p>
                There are at least 16 MPs on a Committee, and the proportion
                of parties reflects the House of Commons, so the government
                always has a majority.
                </p>
            </div>
        </div>

        <div class="business-section__primary">
            <ul class="business-list">
                <?php foreach ($content['data'] as $date => $bills) { ?>
                <li>
                    <span class="business-list__title">
                        <h3>
                            <?= $date ?>
                        </h3>
                    </span>
                    <?php foreach ($bills as $bill) { ?>
                    <p>
                    <a href="<?= $bill['url'] ?>" class="business-list__title">
                        <?= $bill['bill'] ?> &ndash; <?= $bill['sitting'] ?>
                    </a>
                    </p>
                </li>
                <?php }
                    } ?>
            </ul>
        </div>
        <div class="business-section__secondary">
            <p class="rss-feed">
                <a href="<?= WEBPATH . $content['rssurl'] ?>"><?= sprintf(gettext('RSS feed of %s'), $title) ?></a>
            </p>
        </div>
    </div>

    <?php $search_title = 'Search Public Bill Committees';
                include '_search.php'; ?>

</div>
