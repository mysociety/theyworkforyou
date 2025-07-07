                <div class="keyword-alert-accordion">
                  <?php foreach ($keyword_alerts as $index => $alert) { ?>
                    <div class="keyword-alert-accordion__item">
                      <button class="keyword-alert-accordion__button js-accordion-button <?= ($alert['status'] == 'suspended') ? 'keyword-alert-accordion__button--suspended' : '' ?>" href="#accordion-content-<?= $index ?>" aria-expanded="false">
                        <div class="keyword-alert-accordion__button-content">
                          <span class="keyword-alert-accordion__title"><?= _htmlspecialchars($alert['simple_criteria']) ?></span>
                          <?php if (array_key_exists("search_results", $alert)) { ?>
                            <span class="keyword-alert-accordion__subtitle"><?= sprintf(gettext('%d mentions this week'), $alert['search_results']['last_week_count']) ?></span>
                          <?php } ?>
                        </div>
                        <i aria-hidden="true" role="img" class="fi-plus"></i>
                      </button>
                      <div id="accordion-content-<?= $index ?>" class="keyword-alert-accordion__content js-accordion-content" aria-hidden="true" role="img">
                        <div class="keyword-alert-accordion__content-header">
                          <div class="alert__controls">
                            <form action="<?= $actionurl ?>" method="POST">
                              <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                              <?php if ($alert['status'] == 'unconfirmed') { ?>
                                <button type="submit" class="button small" name="action" value="Confirm">
                                  <i aria-hidden="true" class="fi-save"></i>
                                  <span><?= gettext('Confirm alert') ?></span>
                                </button>
                              <?php } elseif ($alert['status'] == 'suspended') { ?>
                                <button type="submit" class="button small" name="action" value="Resume">
                                  <span><?= gettext('Resume alert') ?></span>
                                  <i aria-hidden="true" class="fi-play"></i>
                                </button>
                              <?php } else { ?>
                                <button type="submit" class="button button--outline small" name="action" value="Suspend">
                                  <i aria-hidden="true" class="fi-pause"></i>
                                  <span><?= gettext('Suspend alert') ?></span>
                                </button>
                                <button type="submit" class="button small button--outline-red" name="action" value="Delete">
                                  <i aria-hidden="true" class="fi-trash"></i>
                                  <span><?= gettext('Delete alert') ?></span>
                                </button>
                              </form>
                              <form action="<?= $actionurl ?>" method="POST">
                                <input type="hidden" name="step" value="define">
                                <input type="hidden" name="shown_related" value="1">
                                <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                                <button type="submit" class="button button--outline small" value="Edit">
                                  <i aria-hidden="true" class="fi-page-edit"></i>
                                  <span><?= gettext('Edit alert') ?></span>
                                </button>
                              <?php } ?>
                            </form>
                          </div>
                          <dl class="alert-meta-info">
                            <?php if (array_key_exists("search_results", $alert)) { ?>
                              <div class="content-header-item">
                                <dt><?= gettext('All time') ?></dt>
                                <dd><?= sprintf(gettext('%d mentions'), $alert['search_results']['all_time_count']) ?></dd>
                              </div>
                              <div class="content-header-item">
                                <dt><?= gettext('This week') ?></dt>
                                <dd><?= sprintf(gettext('%d mentions'), $alert['search_results']['last_week_count']) ?></dd>
                              </div>
                              <div class="content-header-item">
                                <dt><?= gettext('Date of last mention') ?></dt>
                                <dd><?= $alert['search_results']['last_mention'] ?></dd>
                              </div>
                            <?php } ?>
                            <a href="/search/?q=<?= _htmlspecialchars($alert['raw']) ?>"><?= gettext('See results for this alert &rarr;') ?></a>
                          </dl>
                        </div>

                        <?php if ($alert["keywords"] or $alert["exclusions"] or $alert["sections"] or array_key_exists('spokenby', $alert)) { ?>
                          <hr>
                        <?php } ?>

                        <?php if ($alert["keywords"]) { ?>
                          <div class="keyword-alert-accordion__keyword-list">
                            <h3>Keywords <strong>included</strong> in this alert:</h3>
                            <ul>
                              <?php foreach ($alert["keywords"] as $keyword) { ?>
                                <li class="keyword-alert-accordion__tag keyword-alert-accordion__tag--included"><?= _htmlspecialchars($keyword) ?>
                              <?php } ?>
                            </ul>
                          </div>
                        <?php } ?>

                        <?php if ($alert["exclusions"]) { ?>
                          <div class="keyword-alert-accordion__keyword-list excluded-keywords">
                            <h3>Keywords <strong>excluded</strong> in this alert:</h3>
                            <ul class="keyword-list">
                              <?php foreach ($alert["exclusions"] as $exclusion) { ?>
                                <li class="keyword-alert-accordion__tag keyword-alert-accordion__tag--excluded"><?= _htmlspecialchars($exclusion) ?>
                              <?php } ?>
                            </ul>
                          </div>
                        <?php } ?>

                        <?php if ($alert['sections']) { ?>
                          <div class="keyword-alert-accordion__keyword-list">
                            <h3>Which <strong>section</strong> should this alert apply to:</h3>
                            <ul class="keyword-list">
                              <?php foreach ($alert["sections_verbose"] as $section) { ?>
                                <li class="keyword-alert-accordion__tag keyword-alert-accordion__tag--included"><?= _htmlspecialchars($section) ?>
                              <?php } ?>
                            </ul>
                          </div>
                        <?php } ?>

                        <?php if (array_key_exists('spokenby', $alert)) { ?>
                          <div class="keyword-alert-accordion__keyword-list">
                            <h3><?= gettext('This alert applies to the following <strong class="bold">representative</span>') ?></h3>
                            <ul>
                              <?php foreach ($alert['spokenby'] as $speaker) { ?>
                                <li class="keyword-alert-accordion__tag keyword-alert-accordion__tag--included"><?= $speaker ?>
                              <?php } ?>
                            </ul>
                          </div>
                        <?php } ?>
                      </div>
                    </div>
                  <?php } ?>
                </div>

                <hr>

                <div class="alert-section__header">
                  <div>
                    <h2>Representative alerts</h2>
                  </div>
                  <form action="<?= $actionurl ?>" method="post">
                    <input type="hidden" name="mp_step" value="mp_alert">
                    <button type="submit" class="button small">
                      <i aria-hidden="true" role="img" class="fi-megaphone"></i>
                      <?= gettext('Create new Representative alert') ?>
                    </button>
                  </form>
                </div>

                <?php if ($current_mp) { ?>
                  <h3><?= gettext('Your MP') ?></h3>
                  <ul class="alerts-manage__list">
                    <li class="alert-section__message">
                      <?= sprintf(gettext('You are not subscribed to an alert for your current MP, %s'), '<strong>' . htmlspecialchars($current_mp->full_name()) . '</strong>') ?>, speaks.
                      <form action="<?= $actionurl ?>" method="post">
                        <input type="hidden" name="pid" value="<?= $current_mp->person_id() ?>">
                        <input type="submit" class="button" value="<?= gettext('Subscribe') ?>">
                      </form>
                    </li>
                  </ul>
                  <?php if (count($own_member_alerts) > 0) { ?>
                    <hr>
                    <p>
                      <?= gettext('You are subscribed to the following alerts about your MP.') ?>
                    </p>
                    <?php include '_own_mp_alerts.php' ?>
                  <?php } else { ?>
                    <?php if (!in_array($own_mp_criteria, $all_keywords)) { ?>
                      <p class="alert-form__subtitle">Alert when <?= _htmlspecialchars($own_mp_criteria) ?> is <strong>mentioned</strong></p>
                      <form action="<?= $actionurl ?>" method="post">
                        <input type="hidden" name="keyword" value="<?= _htmlentities($own_mp_criteria) ?>">
                        <button type="submit" class="button small" name="action" value="Subscribe">
                          <i aria-hidden="true" role="img" class="fi-megaphone"></i>
                          <?= gettext('Create new alert') ?>
                        </button>
                      </form>
                    <?php } ?>
                  <?php } ?>
                <?php } elseif (count($own_member_alerts) > 0) { ?>
                  <div class="alert-form__section">
                    <h3><?= gettext('Your MP') ?> ﹒ <?= $own_member_alerts[0]['spokenby'][0] ?></h3>
                    <?php include '_own_mp_alerts.php' ?>
                  </div>
                <?php } ?>

                <?php foreach ($spoken_alerts as $person_alerts) { ?>
                  <hr>
                  <div class="alert-form__section">
                    <h3><?= _htmlspecialchars(implode(', ', $person_alerts[0]['spokenby'])) ?></h3>
                    <?php foreach ($person_alerts as $alert) { ?>
                      <p class="alert-form__subtitle"><?= _htmlspecialchars($alert['criteria']) ?></p>
                      <div class="alert__controls">
                        <form action="<?= $actionurl ?>" method="POST">
                          <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                          <?php if ($alert['status'] == 'unconfirmed') { ?>
                            <button type="submit" class="button small" name="action" value="Confirm">
                              <i aria-hidden="true" class="fi-save"></i>
                              <span><?= gettext('Confirm alert') ?></span>
                            </button>
                          <?php } elseif ($alert['status'] == 'suspended') { ?>
                            <button type="submit" class="button button-outline small" name="action" value="Resume">
                              <span><?= gettext('Resume alert') ?></span>
                              <i aria-hidden="true" class="fi-play"></i>
                            </button>
                          <?php } else { ?>
                            <button type="submit" class="button button--outline small" name="action" value="Suspend">
                              <i aria-hidden="true" class="fi-pause"></i>
                              <span><?= gettext('Suspend alert') ?></span>
                            </button>
                            <button type="submit" class="button small button--outline-red" name="action" value="Delete">
                              <i aria-hidden="true" class="fi-trash"></i>
                              <span><?= gettext('Delete alert') ?></span>
                            </button>
                          <?php } ?>
                        </form>
                        <form action="<?= $actionurl ?>" method="POST">
                          <?php if (count($alert['words'])) { ?>
                            <input type="hidden" name="step" value="define">
                            <input type="hidden" name="shown_related" value="1">
                            <button type="submit" class="button button--outline small" value="Edit">
                              <i aria-hidden="true" class="fi-page-edit"></i>
                              <span><?= gettext('Edit alert') ?></span>
                            </button>
                          <?php } else { ?>
                            <input type="hidden" name="mp_step" value="mp_confirm">
                            <input type="hidden" name="pid" value="<?= $alert['pid'] ?>">
                            <?php if ($alert['ignore_speaker_votes'] == 1) { ?>
                              <input type="hidden" name="ignore_speaker_votes" value="0">
                              <button type="submit" class="button button--outline small" value="Edit">
                                <i aria-hidden="true" class="fi-page-edit"></i>
                                <span><?= gettext('Include votes') ?></span>
                              </button>
                            <?php } else { ?>
                              <input type="hidden" name="ignore_speaker_votes" value="1">
                              <button type="submit" class="button button--outline small" value="Edit">
                                <i aria-hidden="true" class="fi-page-edit"></i>
                                <span><?= gettext('Ignore votes') ?></span>
                              </button>
                            <?php } ?>
                          <?php } ?>
                        </form>
                      </div>
                    <?php } ?>
                  </div>
                <?php } ?>