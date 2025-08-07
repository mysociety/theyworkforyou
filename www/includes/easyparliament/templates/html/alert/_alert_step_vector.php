                <input type="hidden" name="shown_related" value="1">
                <?php foreach ($keywords as $word) {
                    if (!in_array($word, $skip_keyword_terms)) { ?>
                  <input type="hidden" name="words[]" value="<?= _htmlspecialchars($word) ?>">
                <?php }
                    } ?>
                <input type="hidden" name="this_step" value="add_vector_related">
                <input type="hidden" name="keyword" value="<?= _htmlspecialchars($keyword) ?>">
                <input type="hidden" name="exclusions" value="<?= _htmlspecialchars($exclusions) ?>">
                <input type="hidden" name="representative" value="<?= _htmlspecialchars($representative) ?>">
                <input type="hidden" name="search_section" value="<?= _htmlspecialchars($search_section) ?>">
                <input type="hidden" name="email" id="email" value="<?= _htmlentities($email) ?>">
                <input type="hidden" name="match_all" value="<?= $match_all ? 'on' : ''?>">
                <div class="alert-step" id="step2" role="region" aria-labelledby="step2-header">
                <h2 id="step2-header"><?= gettext('Adding some extras') ?></h2>
                  <div class="alert-form__section">
                  <h3><?= gettext('Current keywords in this alert:') ?></h3>
                    <ul class="keyword-list">
                      <?php foreach ($keywords as $word) {
                          if (!in_array($word, $skip_keyword_terms)) { ?>
                          <li class="keyword-list__tag keyword-list__tag--included"><?= _htmlspecialchars($word) ?>
                      <?php }
                          } ?>
                    </ul>
                  </div>

                  <?php if ($search_results["all_time_count"] > 0 || isset($search_results["last_mention"])) { ?>
                    <hr>
                    <dl class="alert-meta">
                      <div class="alert-meta__results">
                        <?php if ($search_results["all_time_count"] > 0) { ?>
                          <div class="alert-meta__item">
                            <dt><?= gettext('All time') ?></dt>
                            <dd><?= sprintf(gettext('%d mentions'), $search_results["all_time_count"]) ?></dd>
                          </div>
                          <div class="alert-meta__item">
                            <dt><?= gettext('Last 7 days') ?></dt>
                            <dd><?= sprintf(gettext('%d mentions'), $search_results["last_week_count"]) ?></dd>
                          </div>
                        <?php } ?>

                        <?php if (isset($search_results["last_mention"])) { ?>
                          <div class="alert-meta__item">
                          <dt><?= gettext('Date of last mention') ?></dt>
                            <dd><?= $search_results["last_mention"] ?></dd>
                          </div>
                        <?php } ?>
                      </div>
                      <a href="/search/?q=<?= _htmlspecialchars($criteria) ?>" target="_blank" aria-label="See results for this alert - Opens in a new tab"><?= gettext('See results for this alert 	&rarr;') ?></a>
                    </dl>
                  <?php } ?>

                  <hr>

                  <h3><?= gettext('We have also found the following related terms.') ?></h3>

                  <fieldset>
                    <legend><?= gettext('Related Terms') ?></legend>
                    <div class="checkbox-group">
                      <?php foreach ($suggestions as $suggestion) { ?>
                        <input type="hidden" name="related_terms[]" value="<?= _htmlspecialchars($suggestion) ?>">
                        <label>
                          <?php if ($add_all_related == 'on') { ?>
                          <input type="checkbox" name="selected_related_terms[]" value="<?= _htmlspecialchars($suggestion) ?>" checked disabled>
                          <?php } else { ?>
                          <input type="checkbox" name="selected_related_terms[]" value="<?= _htmlspecialchars($suggestion) ?>"<?= in_array($suggestion, $selected_related_terms) ? ' checked' : '' ?>>
                          <?php } ?>
                          <?= _htmlspecialchars($suggestion) ?>

                        </label>
                      <?php } ?>
                                </div>
                                <hr style="width:100%;margin:1em 0;">
                                  <label>
                                  <input type="checkbox" name="add_all_related" id="add-all"<?= $add_all_related == 'on' ? ' checked' : '' ?>>
                                  <?= gettext('Add all related terms') ?>
                                  </label>
 </div>


                  </fieldset>

                  <button type="submit" class="button button--red" name="action" value="Abandon">
                    <i aria-hidden="true" class="fi-trash"></i>
                    <span><?= gettext('Abandon changes') ?></span>
                  </button>
                  <button type="submit" name="step" value="define" class="prev" aria-label="Go back to Step 2"><?= gettext('← Previous') ?></button>
                  <button type="submit" name="step" value="review" class="next" aria-label="Go to Step 3"><?= gettext('Next →') ?></button>

                </div>
