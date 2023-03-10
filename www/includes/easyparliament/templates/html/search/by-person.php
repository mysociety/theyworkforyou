<div class="full-page">
    <div class="full-page__row search-page">

        <form class="js-search-form-without-options">
            <?php include 'form_main.php'; ?>
        </form>

        <div class="search-page__section search-page__section--results">
            <div class="search-page__section__primary">
                <h2>Who says <em class="current-search-term"><?= _htmlentities($searchstring) ?></em> the most?</h2>

              <?php if ( isset($error) ) { ?>
                <?php if ( $error == 'No results' && isset( $house ) && $house != HOUSE_TYPE_ROYAL ) { ?>
                  <ul class="search-result-display-options">
                      <li>
                        <?php if ( $house ==  HOUSE_TYPE_COMMONS) { ?>
                          No results for MPs only
                        <?php } else if ( $house ==  HOUSE_TYPE_LORDS) { ?>
                          No results for Peers only
                        <?php } else if ( $house == HOUSE_TYPE_SCOTLAND) { ?>
                          No results for MSPs only
                        <?php } else if ( $house == HOUSE_TYPE_WALES) { ?>
                          No results for MSs only
                        <?php } else if ( $house ==  HOUSE_TYPE_NI) { ?>
                          No results for MLAs only
                        <?php } ?>
                          |
                          <a href="<?= $this_url->generate('html') ?>">Show results for all speakers</a>
                      </li>
                  </ul>
                <?php } else { ?>
                  <p class="search-results-legend"><?= $error ?></p>
                <?php } ?>
              <?php } ?>

              <?php if ( $wtt ) { ?>
                <p><strong>Now, try reading what a couple of these Lords are saying,
                to help you find someone appropriate. When you've found someone,
                follow the "I want to write to (name of lord)" link on their results page
                to go back to WriteToThem.
                </strong></p>
              <?php } ?>

              <?php if ( isset($speakers) && count($speakers) ) { ?>

                  <?php if ( !$wtt ) { ?>
                    <ul class="search-result-display-options">
                        <li>Results grouped by person</li>
                        <li>
                          <?php if ( $house ==  HOUSE_TYPE_ROYAL) { ?>
                            Show All
                          <?php } else { ?>
                            <a href="<?= $this_url->generate('html') ?>">Show All</a>
                          <?php } ?>
                            |
                          <?php if ( $house ==  HOUSE_TYPE_COMMONS) { ?>
                            MPs only
                          <?php } else { ?>
                            <a href="<?= $this_url->generate('html', array('house'=>1)) ?>">MPs only</a>
                          <?php } ?>
                            |
                          <?php if ( $house ==  HOUSE_TYPE_LORDS) { ?>
                            Peers only
                          <?php } else { ?>
                            <a href="<?= $this_url->generate('html', array('house'=>2)) ?>">Lords only</a>
                          <?php } ?>
                            |
                          <?php if ( $house ==  HOUSE_TYPE_SCOTLAND) { ?>
                            MSPs only
                          <?php } else { ?>
                            <a href="<?= $this_url->generate('html', array('house'=>4)) ?>">MSPs only</a>
                          <?php } ?>
                            |
                          <?php if ( $house ==  HOUSE_TYPE_WALES) { ?>
                            MSs only
                          <?php } else { ?>
                            <a href="<?= $this_url->generate('html', array('house'=>5)) ?>">MSs only</a>
                          <?php } ?>
                            |
                          <?php if ( $house ==  HOUSE_TYPE_NI) { ?>
                            MLAs only
                          <?php } else { ?>
                            <a href="<?= $this_url->generate('html', array('house'=>HOUSE_TYPE_NI)) ?>">MLAs only</a>
                          <?php } ?>
                        </li>
                        <li><a href="<?= $ungrouped_url->generate() ?>">Ungroup results</a></li>
                    </ul>

                    <p class="search-results-legend">The <?= isset($limit_reached) ? '5000 ' : '' ?>most recent mentions of the exact phrase <em class="current-search-term"><?= _htmlentities($searchstring) ?></em>, grouped by speaker name.</p>
                  <?php } ?>

                <table class="search-results-grouped">
                    <thead>
                        <tr>
                            <th>Occurences</th>
                            <th>Speaker</th>
                            <th>Date range</th>
                        </tr>
                    </thead>
                    <tbody>
                      <?php foreach ( $speakers as $pid => $speaker ) { ?>

                        <?php if ( $wtt && $pid == 0 ) { continue; } // skip heading count for WTT lords list ?>

                        <tr>
                            <td><?= $speaker['count'] ?></td>
                            <td>
                              <?php if ( $pid ) { ?>
                                <?php if ( !$wtt || $speaker['left'] == '9999-12-31' ) { ?>
                                  <a href="/search/?q=<?= _htmlentities($searchstring) ?>&amp;pid=<?= $pid ?><?= isset($wtt) && $speaker['left'] == '9999-12-31' ? '&amp;wtt=2' : '' ?>">
                                <?php } ?>
                                <?= isset($speaker['name']) ? $speaker['name'] : 'N/A' ?>
                                <?php if ( !$wtt || $speaker['left'] == '9999-12-31' ) { ?>
                                  </a>
                                <?php } ?>
                                <?php if ( isset($speaker['party']) ) { ?>
                                  <span class="search-results-grouped__speaker-party">(<?= $speaker['party'] ?>)</span>
                                <?php } ?>
                                <?php if ( $house !=  HOUSE_TYPE_LORDS) { ?>
                                  <?= isset($speaker['office']) ? ' - ' . join('; ', $speaker['office']) : '' ?>
                                <?php } ?>
                              <?php } else { // no $pid ?>
                                <?= $speaker['name'] ?>
                              <?php } ?>
                            </td>
                            <td>
                              <?php if ( format_date($speaker['pmindate'], 'M Y') == format_date($speaker['pmaxdate'], 'M Y') ) { ?>
                                <?= format_date($speaker['pmindate'], 'M Y') ?>
                              <?php } else { ?>
                                <?= format_date($speaker['pmindate'], 'M Y') ?>&nbsp;&ndash;&nbsp;<?= format_date($speaker['pmaxdate'], 'M Y') ?>
                              <?php } ?>
                            </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                </table>
              <?php } // end of isset($speakers) ?>

            </div>

            <?php include 'sidebar.php' ?>
        </div>

        <form class="js-search-form-with-options">
            <?php include 'form_main.php'; ?>
            <?php include 'form_options.php'; ?>
        </form>

    </div>
</div>
