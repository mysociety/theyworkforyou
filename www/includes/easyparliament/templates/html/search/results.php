<div class="full-page">
    <div class="full-page__row search-page <?php if (!$searchstring) { ?>search-page--blank<?php } ?>">

        <form class="js-search-form-without-options">
            <?php include 'form_main.php'; ?>
        </form>

      <?php if ($searchstring && !isset($warnings)) { ?>
        <div class="search-page__section search-page__section--results">
            <div class="search-page__section__primary">
              <?php
              # XXX Hack
              if (preg_match('#winter\s+f[eu]el#i', $searchstring)) { ?>
                <h3 style="margin-bottom: 1em;" class="search-result__title"><a href="https://www.theyworkforyou.com/debates/?id=2024-09-10a.712.0">⭐️ Read the debate on the winter fuel regulations</a></h3>
              <?php } ?>

              <?php if ($cons) { ?>
                <?php if (count($cons) > 1) {
                    if ($mp_types['mp'] > 0 && $mp_types['former'] > 0) {
                        $desc = gettext('MPs and former MPs');
                    } elseif ($mp_types['mp'] > 0) {
                        $desc = gettext('MPs');
                    } elseif ($mp_types['former'] > 0) {
                        $desc = gettext('Former MPs');
                    }
                    ?>
                  <h2><?= sprintf(gettext('%s in constituencies matching <em class="current-search-term">%s</em>'), $desc, _htmlentities($searchstring)) ?></h2>
                <?php } elseif ($mp_types['former']) { // count($cons) <= 1?>
                  <h2><?= sprintf(gettext('Former MP for <em class="current-search-term">%s</em>'), _htmlentities($searchstring)) ?></h2>
                <?php } else { // count($cons) <= 1?>
                  <h2><?= sprintf(gettext('MP for <em class="current-search-term">%s</em>'), _htmlentities($searchstring)) ?></h2>
                <?php } ?>
                <?php foreach ($cons as $member) { ?>
                  <?php include('person.php'); ?>
                <?php } ?>
              <?php } ?>

              <?php if ($members) { ?>
                <h2><?= sprintf(gettext('People matching <em class="current-search-term">%s</em>'), _htmlentities($searchstring)) ?></h2>
                <?php foreach ($members as $member) { ?>
                    <?php include('person.php'); ?>
                <?php } ?>
                <hr>
              <?php } ?>

              <?php if ($glossary) { ?>
                <h2>Glossary items matching <em class="current-search-term"><?= _htmlentities($searchstring) ?></em></h2>
                <?php foreach ($glossary as $item) { ?>
                    <?php include('glossary.php'); ?>
                <?php } ?>
                <hr>
              <?php } ?>

              <?php if (isset($pid) && $wtt == 2) { ?>
                <p>I want to <a href="https://www.writetothem.com/lords/?pid=<?= $pid ?>">write to <?= $wtt_lord_name ?></a></p>
              <?php } ?>

              <?php if (isset($error)) { ?>
                There was an error &ndash; <?= $error ?> &ndash; searching for <em class="current-search-term"><?= _htmlentities($searchstring) ?></em>.
              <?php } else { ?>
                <h2>
                  <?php
                            $term = sprintf('<em class="current-search-term">%s</em>', _htmlentities($searchdescription));
                  if ($pagination_links) { ?>
                    <?= sprintf(gettext('Results %s–%s of %s for %s'), $pagination_links['first_result'], $pagination_links['last_result'], $info['total_results'], $term) ?>
                  <?php } elseif ($info['total_results'] == 1) { ?>
                    <?= sprintf(gettext('The only result for %s'), $term) ?>
                  <?php } elseif ($info['total_results'] == 0) { ?>
                    <?= sprintf(gettext('There were no results for %s'), $term) ?>
                  <?php } else { ?>
                    <?= sprintf(gettext('All %s results for %s'), $info['total_results'], $term) ?>
                  <?php } ?>
                </h2>

                  <?php if ($info['spelling_correction']) { ?>
                    <p><?= sprintf(gettext('Did you mean %s?'), '<a href="/search/?q=' . urlencode($info['spelling_correction']) . '">' . _htmlentities($info['spelling_correction_display']) . '</a>') ?></p>
                  <?php } ?>

                  <?php if ($info['total_results']) { ?>
                    <ul class="search-result-display-options">
                      <?php if ($sort_order == 'relevance') { ?>
                        <li><?= gettext('Sorted by relevance') ?></li>
                        <li><?= gettext('Sort by date') ?>: <a href="<?= $urls['newest'] ?>"><?= gettext('newest') ?></a> / <a href="<?= $urls['oldest'] ?>"><?= gettext('oldest') ?></a></li>
                      <?php } elseif ($sort_order == 'oldest') { ?>
                        <li><?= sprintf(gettext('Sort by <a href="%s">relevance</a>'), $urls['relevance']) ?></li>
                        <li><?= gettext('Sorted by date') ?>: <a href="<?= $urls['newest'] ?>"><?= gettext('newest') ?></a> / <?= gettext('oldest') ?></li>
                      <?php } else { ?>
                        <li><?= sprintf(gettext('Sort by <a href="%s">relevance</a>'), $urls['relevance']) ?></li>
                        <li><?= gettext('Sorted by date') ?>: <?= gettext('newest') ?> / <a href="<?= $urls['oldest'] ?>"><?= gettext('oldest') ?></a></li>
                      <?php } ?>
                        <li><a href="<?= $urls['by-person'] ?>"><?= gettext('Group by person') ?></a></li>
                    </ul>
                  <?php } ?>

                  <?php foreach ($rows as $result) { ?>
                    <div class="search-result search-result--generic">
                        <h3 class="search-result__title"><a href="<?= $result['listurl'] ?>"><?= $result['parent']['body'] ?></a> (<?= format_date($result['hdate'], SHORTDATEFORMAT) ?>)</h3>
                        <p class="search-result__description"><?= isset($result['speaker']) ? $result['speaker']['name'] . ': ' : '' ?><?= $result['extract'] ?></p>
                    </div>
                  <?php } ?>

                <hr>

                  <?php if ($pagination_links) { ?>
                    <div class="search-result-pagination">
                      <?php if (isset($pagination_links['prev'])) { ?>
                        <a href="<?= $pagination_links['firstpage']['url'] ?>" title="<?= gettext('First page') ?>">&lt;&lt;</a>
                        <a href="<?= $pagination_links['prev']['url'] ?>" title="<?= gettext('Previous page') ?>">&lt;</a>
                      <?php } ?>
                      <?php foreach ($pagination_links['nums'] as $link) { ?>
                        <a href="<?= $link['url'] ?>"<?= $link['current'] ? ' class="search-result-pagination__current-page"' : '' ?>><?= $link['page'] ?></a>
                      <?php } ?>
                      <?php if (isset($pagination_links['next'])) { ?>
                        <a href="<?= $pagination_links['next']['url'] ?>" title="<?= gettext('Next page') ?>">&gt;</a>
                        <a href="<?= $pagination_links['lastpage']['url'] ?>" title="<?= gettext('Final page') ?>">&gt;&gt;</a>
                      <?php } ?>
                    </div>
                  <?php } ?>
              <?php } # end of !isset($error)?>
            </div>

            <?php include 'sidebar.php' ?>
        </div>
      <?php } ?>

        <form class="js-search-form-with-options">
            <?php include 'form_main.php'; ?>
            <?php include 'form_options.php'; ?>
        </form>

    </div>
</div>
