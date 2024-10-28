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
                          <button class="button small display-none">Discard changes</button>
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
                            <button type="submit" class="button small" name="action" value="Suspend">
                              <span><?= gettext('Suspend alert') ?></span>
                              <i aria-hidden="true" class="fi-pause"></i>
                            </button>
                            <button type="submit" class="button small red" name="action" value="Delete">
                              <span><?= gettext('Delete alert') ?></span>
                              <i aria-hidden="true" class="fi-trash"></i>
                            </button>
                          </form>
                          <form action="<?= $actionurl ?>" method="POST">
                            <input type="hidden" name="step" value="define">
                            <input type="hidden" name="shown_related" value="1">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                            <button type="submit" class="button small" value="Edit">
                              <span><?= gettext('Edit alert') ?></span>
                              <i aria-hidden="true" class="fi-page-edit"></i>
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

                          <a href="/search/?q=<?= $alert['raw'] ?>" class="button small"><?= gettext('See results for this alert') ?></a>
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
                          <i aria-hidden="true" role="img" class="fi-x"></i></li>
                        <?php } ?>
                      </ul>
                      <div class="add-remove-tool display-none">
                        <input type="text" placeholder="e.g.'freedom of information'">
                        <button type="submit" class="prefix">add</button>
                      </div>
                    </div>
                    <?php } ?>

                    <?php if ($alert["exclusions"]) { ?>
                    <div class="keyword-list excluded-keywords alert-page-subsection">
                      <h3 class="heading-with-bold-word">Keywords <span class="bold">excluded</span> in this alert:</h3>
                      <ul>
                        <?php foreach ($alert["exclusions"] as $exclusion) { ?>
                        <li class="label label--red"><?= _htmlspecialchars($exclusion) ?>
                          <i aria-hidden="true" role="img" class="fi-x"></i></li>
                        <?php } ?>
                      </ul>
                      <div class="add-remove-tool display-none">
                        <input type="text" placeholder="e.g.'freedom of information'">
                        <button type="submit" class="prefix">add</button>
                      </div>
                    </div>
                    <?php } ?>

                    <?php if ($alert['sections']) { ?>
                    <div class="keyword-list alert-page-subsection">
                      <h3 class="display-none"><label for="sections">Which section should this alert apply to?</label></h3>
                      <select name="sections" id="sections" class="display-none">
                        <option value="uk-parliament">All sections</option>
                        <option value="uk-parliament">UK Parliament</option>
                        <option value="scottish-parliament">Scottish Parliament</option>
                      </select>
                      <h3 class="heading-with-bold-word">Which <span class="bold">section</span> should this alert apply to:</h3>
                      <ul>
                        <?php foreach ($alert["sections_verbose"] as $section) { ?>
                        <li class="label label--red"><?= _htmlspecialchars($section) ?>
                          <i aria-hidden="true" role="img" class="fi-x"></i></li>
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
                            <i aria-hidden="true" role="img" class="fi-x"></i></li>
                        <?php } ?>
                        </ul>
                        <div class="add-remove-tool display-none">
                          <input type="text" placeholder="e.g.'freedom of information'">
                          <button type="submit" class="prefix">add</button>
                        </div>
                      </div>
                    <?php } ?>

                    <button class="display-none" style="margin: -1rem 0rem 3rem;">Save changes</button>
                    <button class="display-none" style="margin: -1rem 0rem 3rem;">Discard changes</button>

                  </div>
                </div>
                <?php } ?>

                <hr>

                    <div class="alert-page-header">
                      <div>
                        <h2>Representative alerts</h2>
                      </div>
                      <form action="<?= $actionurl ?>" method="post">
                        <input type="hidden" name="step" value="mp_alert">
                        <button type="submit" class="button small">
                          <?= gettext('Create new MP alert') ?>
                          <i aria-hidden="true" role="img" class="fi-megaphone"></i>
                        </button>
                      </form>
                      </div>
                    <?php if ($current_mp) { ?>
                      <ul class="alerts-manage__list">
                          <li>
                              <?= sprintf(gettext('You are not subscribed to an alert for your current MP, %s'), $current_mp->full_name()) ?>.
                              <form action="<?= $actionurl ?>" method="post">
                                  <input type="hidden" name="pid" value="<?= $current_mp->person_id() ?>">
                                  <input type="submit" class="button" value="<?= gettext('Subscribe') ?>">
                              </form>
                          </li>
                      </ul>
                    <?php } else { ?>
                      <?php foreach ($own_member_alerts as $alert) { ?>
                        <div class="alert-page-subsection">
                          <h3 class="alert-page-subsection--heading"><?= gettext('Your MP') ?> ﹒ XXX</h3>

                          <p class="alert-page-subsection--subtitle"><?= _htmlspecialchars($alert['criteria']) ?></p>
                          <div class="alert-page-alert-controls">
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
                              <button typ="submit" class="button small" value="Suspend">
                                <span><?= gettext('Suspend alert') ?></span>
                                <i aria-hidden="true" class="fi-pause"></i>
                              </button>
                              <button typ="submit" class="button small" value="Delete">
                                <span><?= gettext('Delete alert') ?></span>
                                <i aria-hidden="true" class="fi-trash"></i>
                              </button>
                            </form>
                            <form action="<?= $actionurl ?>" method="POST">
                              <input type="hidden" name="step" value="define">
                              <input type="hidden" name="shown_related" value="1">
                              <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                              <button type="submit" class="button small" value="Edit">
                                <span><?= gettext('Edit alert') ?></span>
                                <i aria-hidden="true" class="fi-page-edit"></i>
                              </button>
                            </form>
                              <?php } ?>

                          <?php if (!in_array(implode('', $alert['spokenby']), $all_keywords)) { ?>
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
                      </div>
                      <?php } ?>
                    <?php } ?>

                    <?php foreach ($spoken_alerts as $alert) { ?>
                      <div class="alert-page-subsection">
                        <h3 class="alert-page-subsection--heading"><?= _htmlspecialchars(implode(', ', $alert['spokenby'])) ?></h3>

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
                              <button type="submit" class="button small" name="action" value="Resume">
                                <span><?= gettext('Resume alert') ?></span>
                                <i aria-hidden="true" class="fi-play"></i>
                              </button>
                              <?php } else { ?>
                              <button type="submit" class="button small" name="action" value="Suspend">
                                <span><?= gettext('Suspend alert') ?></span>
                                <i aria-hidden="true" class="fi-pause"></i>
                              </button>
                              <button type="submit" class="button small" name="action" value="Delete">
                                <span><?= gettext('Delete alert') ?></span>
                                <i aria-hidden="true" class="fi-trash"></i>
                              </button>
                            </form>
                            <form action="<?= $actionurl ?>" method="POST">
                              <input type="hidden" name="step" value="define">
                              <input type="hidden" name="t" value="<?= _htmlspecialchars($alert['token']) ?>">
                              <button type="submit" class="button small" value="Edit">
                                <span><?= gettext('Edit alert') ?></span>
                                <i aria-hidden="true" class="fi-page-edit"></i>
                              </button>
                              <?php } ?>
                            </form>

                          <?php if (!in_array(implode('', $alert['spokenby']), $all_keywords)) { ?>
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
                      </div>
                    <?php } ?>
                </div>