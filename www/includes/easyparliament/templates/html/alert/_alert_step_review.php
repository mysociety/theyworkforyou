                <?php foreach ($keywords as $word) {
                    if (!in_array($word, $skip_keyword_terms)) { ?>
                      <input type="hidden" name="words[]" value="<?= _htmlspecialchars($word) ?>">
                    <?php }
                    } ?>
                <?php foreach ($selected_related_terms as $word) { ?>
                  <input type="hidden" name="selected_related_terms[]" value="<?= _htmlspecialchars($word) ?>">
                <?php } ?>
                <input type="hidden" name="add_all_related" value="<?= $add_all_related ?>">
                <input type="hidden" name="this_step" value="review">
                <input type="hidden" name="keyword" value="<?= _htmlspecialchars($keyword) ?>">
                <input type="hidden" name="exclusions" value="<?= _htmlspecialchars($exclusions) ?>">
                <input type="hidden" name="representative" value="<?= _htmlspecialchars($representative) ?>">
                <input type="hidden" name="search_section" value="<?= _htmlspecialchars($search_section) ?>">
                <input type="hidden" name="email" id="email" value="<?= _htmlentities($email) ?>">
                <input type="hidden" name="match_all" value="<?= $match_all ? 'on' : ''?>">
                <!-- Step 4 (Review) -->
                <div class="alert-step" id="step3" role="region" aria-labelledby="step3-header">
                  <h2 id="step3-header"><?= gettext('Review Your Alert') ?></h2>

                  <div class="alert-form__section">
                    <?php if ($match_all) { ?>
                      <h3><?= gettext('If you click \'save alert\', you will get an alert if all of these words are in a speech') ?>:</h3>
                    <?php } else { ?>
                      <h3><?= gettext('You will get an alert if any of these words are in a speech') ?>:</h3>
                    <?php } ?>
                    <ul class="keyword-list">
                      <?php foreach ($keywords as $word) { ?>
                      <li class="keyword-list__tag keyword-list__tag--included"><?= _htmlspecialchars($word) ?>
                      <?php } ?>
                    </ul>
                  </div>

                  <?php if ($exclusions) { ?>
                  <div class="excluded-keywords alert-form__section">
                  <h3><?= gettext('Unless the speech also includes these words') ?>:</h3>
                    <ul class="keyword-list">
                      <?php foreach (explode(" ", $exclusions) as $word) { ?>
                      <li class="keyword-list__tag keyword-list__tag--excluded"><?= _htmlspecialchars($word) ?>
                      <?php } ?>
                    </ul>
                  </div>
                  <?php } ?>

                  <div class="alert-form__section">
                  <?php if (count($sections) > 0) { ?>
                    <h3><?= gettext('And only if the speech is in') ?>:</h3>
                      <ul class="keyword-list">
                      <?php foreach ($sections as $word) { ?>
                        <li class="keyword-list__tag keyword-list__tag--included"><?= _htmlspecialchars($word) ?>
                      <?php } ?>
                      </ul>
                  <?php } else { ?>
                    <h3><?= gettext('in the UK, Scottish or Welsh Parliaments or Northern Ireland Assembly') ?></h3>
                  <?php } ?>
                  </div>

                  <?php if (count($members) > 0) { ?>
                  <div class="alert-form__section">
                  <h3><?= gettext('And only when spoken by') ?></h3>
                    <ul class="keyword-list">
                      <?php foreach ($members as $member) { ?>
                      <li class="keyword-list__tag keyword-list__tag--included"><?= $member['given_name'] ?> <?= $member['family_name'] ?>
                      <?php } ?>
                    </ul>
                  </div>
                  <?php } ?>

                  <?php if ($search_results["all_time_count"] > 0 || isset($last_mention)) { ?>
                    <hr>
                    <dl class="alert-meta">
                      <h3><?= gettext("Alert statistics") ?></h3>

                      <div class="alert-meta__results">
                          <div class="alert-meta__item">
                            <dt><?= gettext('Last 7 days') ?></dt>
                            <dd><?= sprintf(gettext('%d mentions'), $search_results["all_time_count"]) ?></dd>
                          </div>

                          <div class="alert-meta__item">
                            <dt><?= gettext('Date of last mention') ?></dt>
                            <dd><?= $search_results["last_mention"] ?></dd>
                          </div>
                      </div>

                      <a href="/search/?q=<?= _htmlspecialchars($criteria) ?>" target="_blank" aria-label="See results for this alert - Opens in a new tab"><?= gettext('See results for this alert 	&rarr;') ?></a>
                    </dl>
                  <?php } ?>

                  <hr>
                  <?php include('_alert_form_abandon_button.php') ?>
                  <button type="submit" name="step" value="define" class="prev" aria-label="Go back to Step 2">Go Back</button>
                  <button class="button" type="submit" name="step" value="confirm">
                    <i aria-hidden="true" class="fi-save"></i>
                    <span><?= gettext('Save alert') ?></span>
                  </button>

                  <?php if ($token) { ?>
                  <button type="submit" class="button button--red" name="action" value="Delete">
                    <i aria-hidden="true" class="fi-trash"></i>
                    <span><?= gettext('Delete alert') ?></span>
                  </button>
                  <?php } ?>
                </div>
