<div class="full-page legacy-page static-page">
  <div class="full-page__row">
    <div class="panel">
      <div class="stripe-side">
        <div class="main">
        <?php if (isset($error)) { ?>
            <p>
              <?= $error ?>
            </p>
        <?php } ?>

            <h4>Found <?= $glossary->num_search_matches ?> <?= ngettext('match', 'matches', $glossary->num_search_matches) ?> for <em><?= $glossary->query ?></em></h4>
            <p>It seems we already have <?= ngettext('a definition', 'some definitions', $glossary->num_search_matches) ?> for that. Would you care to see <?= ngettext('it', 'them', $glossary->num_search_matches) ?>?</p>
            <ul class="glossary">
            <?php
              foreach ($glossary->search_matches as $match) {
                  $URL = new \MySociety\TheyWorkForYou\Url('glossary');
                  $URL->insert(['gl' => $match['glossary_id']]);
                  $URL->remove(['g']);
                  $term_link = $URL->generate('url');
                  ?>
                  <li>
                    <a href="<?= $term_link ?>"><?= $match['title']?></a>
                  </li>
            <?php } ?>
            </ul>
        </div>
      </div>
    </div>
  </div>
</div>
