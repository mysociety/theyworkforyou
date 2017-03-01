    <div class="full-page__row">
        <div class="business-section">
          <div class="business-section__header">
              <h1 class="business-section__header__title">
              Recent House of Commons Votes
              </h1>
          </div>
          <div class="business-section__primary">
               <?php
               if ( isset($divisions) && $divisions ) {
                 $current_date = '';
               ?>
               <ul class="business-list">
                    <?php foreach ($divisions as $division) { ?>
                    <li id="<?= $division['division_id'] ?>">
                        <?php if ($current_date != $division['date']) { ?>
                        <span class="business-list__title">
                        <h3><?= format_date($division['date'], LONGERDATEFORMAT) ?></h3>
                        </span>
                        <?php
                          $current_date = $division['date'];
                        } ?>
                        <a href="<?= $division['debate_url'] ?>" class="business-list__title">
                            <h3><?= $division['division_title'] ?></h3>
                            <span class="business-list__meta">Division number <?= $division['number'] ?></span>
                        </a>
                        <p class="business-list__excerpt">
                            A majority of MPs <?= preg_replace('/(voted\s+(?:for|against|not to|to|in favour))/', '<b>\1</b>', $division['text']) ?>
                            <br>
                            <span class="business-list__meta">
                                <?= $division['summary'] ?>
                                <?= $division['mp_vote'] !== '' ? '. <b>Your MP, ' . $mp_name . ', ' . $division['mp_vote'] . '.</b>': '' ?>
                            </span>
                        </p>
                    </li>
                    <?php } ?>
               </ul>
               <?php } ?>
            </div>
            <div class="business-section__secondary">
                <div class="business-section__what-is-this">
                    <h3>What is this?</h3>

                    <p>This list only contains votes that are part of a TheyWorkForYou policy. To see all recent votes have a look at <a href="http://www.publicwhip.org.uk/divisions.php">PublicWhip</a></p>

                    <p class="policy-votes__byline">Vote information from <a href="http://www.publicwhip.org.uk/">PublicWhip</a>. Last updated: <?= $last_updated ?></p>
                </div>
            </div>
        </div>
    </div>
