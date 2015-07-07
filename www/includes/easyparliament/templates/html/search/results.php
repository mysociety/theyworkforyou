<div class="full-page">
    <div class="full-page__row search-page <?php if ( !$searchstring ) { ?>search-page--blank<?php } ?>">

        <?php include 'form.php'; ?>

        <?php if ( $searchstring && !isset($warnings) ) { ?>
        <div class="search-page__section search-page__section--results">
            <div class="search-page__section__primary">
                <?php if ( $cons ) { ?>
                    <?php if ( count($cons) > 1 ) { ?>
                    <h2>MPs in constituencies matching <em class="current-search-term"><?= $info['s'] ?></em></h2>
                    <?php } else { ?>
                    <h2>MP for <em class="current-search-term"><?= $info['s'] ?></em></h2>
                    <?php } ?>
                    <?php foreach ( $cons as $member ) { ?>
                        <?php include('person.php'); ?>
                    <?php } ?>
                <?php } ?>

                <?php if ( $members ) { ?>
                <h2>People matching <em class="current-search-term"><?= $info['s'] ?></em></h2>

                <?php foreach ( $members as $member ) { ?>
                    <?php include('person.php'); ?>
                <?php } ?>

                <hr>
                <?php } ?>

                <?php if ($glossary) { ?>
                <h2>Glossary items matching <em class="current-search-term"><?= $info['s'] ?></em></h2>

                  <?php foreach ( $glossary as $item ) { ?>
                    <?php include('glossary.php'); ?>
                  <?php } ?>

                <hr>
                <?php } ?>

                <?php if ( isset($pid) && $wtt == 2 ) { ?>
                    <p>I want to <a href="https://www.writetothem.com/lords/?pid=<?= $pid ?>">write to <?= $wtt_lord_name ?></a></p>
                <?php } ?>

                <?php if ( isset($error) ) { ?>
                    There was an error &ndash; <?= $error ?> &ndash; searching for <em class="current-search-term"><?= _htmlentities($searchstring) ?></em>.
                <?php } else { ?>
                    <h2>
                    <?php if ( $pagination_links ) { ?>
                    Results <?= $pagination_links['first_result'] ?>&ndash;<?= $pagination_links['last_result'] ?> of <?= $info['total_results'] ?>
                    <?php } else if ( $info['total_results'] == 1 ) { ?>
                    The only result
                    <?php } else if ( $info['total_results'] == 0 ) { ?>
                    There were no results
                    <?php } else { ?>
                    All <?= $info['total_results'] ?> results
                    <?php } ?>
                    for <em class="current-search-term"><?= _htmlentities($info['s']) ?></em></h2>

                    <?php if ( $info['spelling_correction'] ) { ?>
                    <p>Did you mean <a href="/search/?q=<?= urlencode($info['spelling_correction']) ?>"><?= _htmlentities( $info['spelling_correction'] ) ?></a>?</p>
                    <?php } ?>

                    <?php if ( $info['total_results'] ) { ?>
                    <ul class="search-result-display-options">
                        <?php if ( $sort_order == 'relevance' ) { ?>
                        <li>Sorted by relevance</li>
                        <li>Sort by date: <a href="<?= $urls['newest'] ?>">newest</a> / <a href="<?= $urls['oldest'] ?>">oldest</a></li>
                        <?php } else if ( $sort_order == 'oldest' ) { ?>
                        <li>Sort by <a href="<?= $urls['relevance'] ?>">relevance</a><li>
                        <li>Sorted by date: <a href="<?= $urls['newest'] ?>">newest</a> / oldest</li>
                        <?php } else { ?>
                        <li>Sort by <a href="<?= $urls['relevance'] ?>">relevance</a><li>
                        <li>Sorted by date: newest / <a href="<?= $urls['oldest'] ?>">oldest</a></li>
                        <?php } ?>
                        <li><a href="<?= $urls['by-person'] ?>">Group by person</a></li>
                    </ul>
                    <?php } ?>

                    <?php foreach ( $rows as $result ) { ?>
                    <div class="search-result search-result--generic">
                    <h3 class="search-result__title"><a href="<?= $result['listurl'] ?>"><?= $result['parent']['body'] ?></a> (<?= format_date($result['hdate'], SHORTDATEFORMAT) ?>)</h3>
                        <p class="search-result__description"><?= isset($result['speaker']) ? $result['speaker']['name'] . ': ' : '' ?><?= $result['extract'] ?></p>
                    </div>
                    <?php } ?>

                    <hr>

                    <?php if ( $pagination_links ) { ?>
                    <div class="search-result-pagination">
                        <?php if ( isset($pagination_links['prev']) ) { ?>
                        <a href="<?= $pagination_links['firstpage']['url'] ?>">&lt;&lt;</a>
                        <a href="<?= $pagination_links['prev']['url'] ?>">&lt;</a>
                        <?php }
                        foreach ( $pagination_links['nums'] as $link ) { ?>
                        <a href="<?= $link['url'] ?>"<?= $link['current'] ? ' class="search-result-pagination__current-page"' : '' ?>><?= $link['page'] ?></a>
                        <?php }
                        if ( isset($pagination_links['next']) ) { ?>
                        <a href="<?= $pagination_links['next']['url'] ?>">&gt;</a>
                        <a href="<?= $pagination_links['lastpage']['url'] ?>">&gt;&gt;</a>
                        <?php } ?>
                    </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <?php include 'sidebar.php' ?>
        </div>
        <?php } ?>

    </div>
</div>
