              <div class="accordion">
                <?php foreach ($keyword_alerts as $index => $alert) { ?>
                <div class="accordion-item">
                <button class="accordion-button" href="#accordion-content-<?= $index ?>" aria-expanded="false">
                    <div class="accordion-button--content">
                      <span class="content-title"><?= _htmlspecialchars($alert['criteria']) ?></span>
                      <?php if (array_key_exists("mentions", $alert)) { ?>
                      <span class="content-subtitle"><?= sprintf(gettext('%d mentions this week'), $alert['mentions']) ?></span>
                      <?php } ?>
                    </div>
                    <i aria-hidden="true" role="img" class="fi-plus"></i>
                  </button>
                  <div id="accordion-content-<?= $index ?>" class="accordion-content" aria-hidden="true" role="img">
                    <div class="accordion-content-header">
                      <div class="alert-controller-wrapper">
                        <form action="<?= $actionurl ?>" method="POST">
                          <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                          <?php if ($alert['status'] == 'unconfirmed') { ?>
                            <button type="submit" class="button small" name="action" value="Confirm">
                              <span><?= gettext('Confirm alert') ?></span>
                              <i aria-hidden="true" class="fi-save"></i>
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
                            <button type="submit" class="button small button--red" name="action" value="Delete">
                              <i aria-hidden="true" class="fi-trash"></i>
                              <span><?= gettext('Delete alert') ?></span>
                            </button>
                          </form>
                          <form action="<?= $actionurl ?>" method="POST">
                            <input type="hidden" name="step" value="define">
                            <input type="hidden" name="shown_related" value="1">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                            <button type="submit" class="button small" value="Edit">
                              <i aria-hidden="true" class="fi-page-edit"></i>
                              <span><?= gettext('Edit alert') ?></span>
                            </button>
                          <?php } ?>
                          </form>
                      </div>
                      <dl class="alert-meta-info">
                          <?php if (array_key_exists("mentions", $alert)) { ?>
                           <div class="content-header-item">
                             <dt><?= gettext('This week') ?></dt>
                             <dd><?= sprintf(gettext('%d mentions'), $alert['mentions']) ?></dd>
                           </div>
                          <?php } ?>

                          <?php if (array_key_exists("last_mention", $alert)) { ?>
                          <div class="content-header-item">
                          <dt><?= gettext('Date of last mention') ?></dt>
                          <dd><?= $alert['last_mention'] ?></dd>
                          </div>
                          <?php } ?>

                          <a href="/search/?q=<?= $alert['raw'] ?>"><?= gettext('See results for this alert &rarr;') ?></a>
                        </dl>
                    </div>

                    <?php if ($alert["keywords"] or $alert["exclusions"] or $alert["sections"] or array_key_exists('spokenby', $alert)) { ?>
                    <hr>
                    <?php } ?>

                    <?php if ($alert["keywords"]) { ?>
                    <div class="keyword-list alert-page-subsection">
                      <h3 class="heading-with-bold-word">Keywords <span class="bold">included</span> in this alert:</h3>
                      <ul>
                        <?php foreach ($alert["keywords"] as $keyword) { ?>
                        <li class="label label--primary-light"><?= _htmlspecialchars($keyword) ?>
                        <?php } ?>
                      </ul>
                    </div>
                    <?php } ?>

                    <?php if ($alert["exclusions"]) { ?>
                    <div class="keyword-list excluded-keywords alert-page-subsection">
                      <h3 class="heading-with-bold-word">Keywords <span class="bold">excluded</span> in this alert:</h3>
                      <ul>
                        <?php foreach ($alert["exclusions"] as $exclusion) { ?>
                        <li class="label label--red"><?= _htmlspecialchars($exclusion) ?>
                        <?php } ?>
                      </ul>
                    </div>
                    <?php } ?>

                    <?php if ($alert['sections']) { ?>
                    <div class="keyword-list alert-page-subsection">
                      <h3 class="heading-with-bold-word">Which <span class="bold">section</span> should this alert apply to:</h3>
                      <ul>
                        <?php foreach ($alert["sections_verbose"] as $section) { ?>
                        <li class="label label--primary-light"><?= _htmlspecialchars($section) ?>
                        <?php } ?>
                      </ul>
                    </div>
                    <?php } ?>

                    <!-- Only to be displayed if there is a person in this query -->

                    <?php if (array_key_exists('spokenby', $alert)) { ?>
                      <div class="keyword-list alert-page-subsection">
                        <h3 class="heading-with-bold-word"><?= gettext('This alert applies to the following <span class="bold">representative</span>') ?></h3>
                        <ul>
                        <?php foreach ($alert['spokenby'] as $speaker) { ?>
                        <li class="label label--primary-light"><?= $speaker ?>

                        <?php } ?>
                        </ul>
                      </div>
                    <?php } ?>
                  </div>
                </div>
                <?php } ?>

                <hr>

                    <div class="alert-page-header">
                      <div>
                        <h2>Representative alerts</h2>
                      </div>
                      <form action="<?= $actionurl ?>" method="post">
                        <input type="hidden" name="mp_step" value="mp_alert">
                        <button type="submit" class="button small">
                          <?= gettext('Create new MP alert') ?>
                          <i aria-hidden="true" role="img" class="fi-megaphone"></i>
                        </button>
                      </form>
                      </div>
                    <?php if ($current_mp) { ?>
                      <h3 class="alert-page-subsection--heading"><?= gettext('Your MP') ?></h3>
                      <ul class="alerts-manage__list">
                          <li>
                              <?= sprintf(gettext('You are not subscribed to an alert for your current MP, %s'), '<strong>' . htmlspecialchars($current_mp->full_name()) . '</strong>') ?>.
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
                      <?php } ?>
                    <?php } elseif (count($own_member_alerts) > 0) { ?>
                        <div class="alert-page-subsection">
                          <h3 class="alert-page-subsection--heading"><?= gettext('Your MP') ?> ﹒ <?= $own_member_alerts[0]['spokenby'][0] ?></h3>

                          <?php include '_own_mp_alerts.php' ?>
                      </div>
                    <?php } ?>

                    <?php foreach ($spoken_alerts as $person_alerts) { ?>
                      <hr>
                      <div class="alert-page-subsection">
                        <h3 class="alert-page-subsection--heading"><?= _htmlspecialchars(implode(', ', $person_alerts[0]['spokenby'])) ?></h3>

                          <?php foreach ($person_alerts as $alert) { ?>
                          <p class="alert-page-subsection--subtitle"><?= _htmlspecialchars($alert['criteria']) ?>
                          <div class="alert-page-alert-controls">
                            <form action="<?= $actionurl ?>" method="POST">
                              <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                              <?php if ($alert['status'] == 'unconfirmed') { ?>
                                <button type="submit" class="button small" name="action" value="Confirm">
                                  <span><?= gettext('Confirm alert') ?></span>
                                  <i aria-hidden="true" class="fi-save"></i>
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
                              <button type="submit" class="button small button--red" name="action" value="Delete">
                                <i aria-hidden="true" class="fi-trash"></i>
                                <span><?= gettext('Delete alert') ?></span>
                              </button>
                            </form>
                            <form action="<?= $actionurl ?>" method="POST">
                              <input type="hidden" name="step" value="define">
                              <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                              <button type="submit" class="button small" value="Edit">
                                <i aria-hidden="true" class="fi-page-edit"></i>
                                <span><?= gettext('Edit alert') ?></span>
                              </button>
                              <?php } ?>
                            </form>
                          </div>
                          <?php } ?>
                        <?php if (!in_array(implode('', $person_alerts[0]['spokenby']), $all_keywords)) { ?>
                        <p class="alert-page-subsection--subtitle">Alert when <?= _htmlspecialchars(implode(', ', $alert['spokenby'])) ?> is <strong>mentioned</strong></p>
                        <form action="<?= $actionurl ?>" method="post">
                          <input type="hidden" name="keyword" value="<?= _htmlentities(implode('', $alert['spokenby'])) ?>">
                          <button type="submit" class="button small" name="action" value="Subscribe">
                            <?= gettext('Create new alert') ?>
                            <i aria-hidden="true" role="img" class="fi-megaphone"></i>
                          </button>
                        </form>
                        <?php } ?>
                      </div>
                    <?php } ?>
                </div>
