          <div class="alert-section">
            <div class="alert-section__primary">
              <div class="alert-page-section">
                <div class="alert-creation-steps">
                  <h1><?php if ($token) { ?>
                    <?= gettext('Edit Alert') ?>
                  <?php } else { ?>
                    <?= gettext('Create Alert') ?>
                  <?php } ?>
                  </h1>

                  <form action="<?= $actionurl ?>" method="POST" id="create-alert-form">
                    <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                  <?php if (!$step or $step == "define") { ?>

                    <input type="hidden" name="this_step" value="define">
                    <div class="alert-step" id="step1" role="region" aria-labelledby="step1-header">
                    <h2 id="step1-header"><?= gettext('Define alert') ?></h2>

                      <?php if (!$email_verified) { ?>
                      <div class="alert-page-subsection">
                        <?php if (isset($errors['email'])) { ?>
                          <span class="alert-page-error"><?= $errors['email'] ?></span>
                        <?php } ?>
                        <label for="email"><?= gettext('Your email address') ?></label>
                        <input type="email" class="form-control" placeholder="<?= gettext('Your email address') ?>" name="email" id="email" value="<?= _htmlentities($email) ?>">
                      </div>
                      <?php } ?>

                      <div class="alert-page-subsection">
                      <label for="words[]"><?= gettext('What word or phrase would you like to recieve alerts about?') ?></label>
                        <?php if (isset($errors['alertsearch']) && $submitted) { ?>
                          <span class="alert-page-error"><?= $errors['alertsearch'] ?></span>
                        <?php } ?>
                        <input type="text" id="words0" name="words[]" aria-required="true" value="<?= count($keywords) > 0 ? _htmlspecialchars($keywords[0]) : '' ?>" placeholder="Eg. 'Freedom of Information', 'FOI'">
                        <?php foreach (array_slice($keywords, 1) as $index => $word) { ?>
                          <input type="text" id="words<?= $index + 1 ?>" name="words[]" value="<?= _htmlspecialchars($word) ?>" placeholder="Eg. 'Freedom of Information', 'FOI'">
                        <?php } ?>
                        <?php if ($addword) { ?>
                        <input type="text" id="words<?= count($words) ?>" name="words[]" value="" placeholder="Eg. 'Freedom of Information', 'FOI'">
                        <?php } ?>
                        <button class="button" type="submit" name="addword" value="add">
                        <span><?= gettext('Add word') ?></span>
                          <i aria-hidden="true" class="fi-save"></i>
                        </button>
                      </div>

                      <div class="alert-page-subsection">
                        <label for="exclusions"><?= gettext('Is there anything you would not like to recieve alerts about? (optional)') ?></label>
                        <input type="text" id="exclusions" name="exclusions" aria-required="true" value="<?= _htmlspecialchars($exclusions) ?>" placeholder="Eg. 'Freedom of Information', 'FOI'">
                      </div>

                      <div class="alert-page-subsection">
                      <label for="select-section"><?= gettext('Would you like to limit which Parliaments and Assemblies we alert about?') ?></label>
                        <select name="search_section" id="select-section">
                        <option value=""><?= gettext('Send alerts for everywhere.') ?></option>
                          <optgroup label="<?= gettext('UK Parliament') ?>">
                              <option value="uk"<?= $search_section == 'uk' ? ' selected' : '' ?>><?= gettext('All UK') ?></option>
                              <option value="debates"<?= $search_section == 'debates' ? ' selected' : '' ?>><?= gettext('House of Commons debates') ?></option>
                              <option value="whalls"<?= $search_section == 'whalls' ? ' selected' : '' ?>><?= gettext('Westminster Hall debates') ?></option>
                              <option value="lords"<?= $search_section == 'lords' ? ' selected' : '' ?>><?= gettext('House of Lords debates') ?></option>
                              <option value="wrans"<?= $search_section == 'wrans' ? ' selected' : '' ?>><?= gettext('Written answers') ?></option>
                              <option value="wms"<?= $search_section == 'wms' ? ' selected' : '' ?>><?= gettext('Written ministerial statements') ?></option>
                              <option value="standing"<?= $search_section == 'standing' ? ' selected' : '' ?>><?= gettext('Bill Committees') ?></option>
                              <option value="future"<?= $search_section == 'future' ? ' selected' : '' ?>><?= gettext('Future Business') ?></option>
                          </optgroup>
                          <optgroup label="<?= gettext('Northern Ireland Assembly') ?>">
                              <option value="ni"<?= $search_section == 'ni' ? ' selected' : '' ?>><?= gettext('Debates') ?></option>
                          </optgroup>
                          <optgroup label="<?= gettext('Scottish Parliament') ?>">
                              <option value="scotland"<?= $search_section == 'scotland' ? ' selected' : '' ?>><?= gettext('All Scotland') ?></option>
                              <option value="sp"<?= $search_section == 'sp' ? ' selected' : '' ?>><?= gettext('Debates') ?></option>
                              <option value="spwrans"<?= $search_section == 'spwrans' ? ' selected' : '' ?>><?= gettext('Written answers') ?></option>
                          </optgroup>
                          <optgroup label="<?= gettext('Senedd / Welsh Parliament') ?>">
                              <option value="wales"<?= $search_section == 'wales' ? ' selected' : '' ?>><?= gettext('Debates') ?></option>
                          </optgroup>
                          <optgroup label="<?= gettext('London Assembly') ?>">
                              <option value="lmqs"<?= $search_section == 'lmqs' ? ' selected' : '' ?>><?= gettext('Questions to the Mayor') ?></option>
                          </optgroup>
                        </select>
                      </div>

                        <div class="alert-page-subsection">
                        <label for="representative"><?= gettext('Would you like to only alert when a particular person speaks? (optional)') ?></label>
                          <?php if (isset($errors["representative"])) { ?>
                            <?php if (count($members) > 0) { ?>
                              <span class="alert-page-error"><?= $errors["representative"] ?></span>
                            <?php foreach ($members as $index => $member) {
                                $name = member_full_name($member['house'], $member['title'], $member['given_name'], $member['family_name'], $member['lordofname']);
                                if ($member['constituency']) {
                                    $name .= ' (' . gettext($member['constituency']) . ')';
                                } ?>
                              <input type="radio" name="pid" id="representative_<?= $index ?>" value="<?= $member['person_id'] ?>">
                              <label class="alert-page-option-label" for="representative_<?= $index ?>"><?= $name ?></label><br>
                            <?php } ?>
                          <?php } ?>
                          <p><?= gettext("Or edit the name") ?></p>
                        <?php } ?>
                          <input type="text" id="representative" name="representative" value="<?= _htmlspecialchars($representative) ?>" aria-required="true">
                      </div>


                      <button type="submit" name="step" value="review" class="next" aria-label="Go to Step 2">Next</button>
                    </div>
                  <?php } elseif ($step == "add_vector_related") { ?>

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
                    <div class="alert-step" id="step2" role="region" aria-labelledby="step2-header">
                    <h2 id="step2-header"><?= gettext('Adding some extras') ?></h2>
                      <div class="keyword-list alert-page-subsection">
                      <h3 class="heading-with-bold-word"><?= gettext('Current keywords in this alert:') ?></h3>
                        <ul>
                          <?php foreach ($keywords as $word) {
                              if (!in_array($word, $skip_keyword_terms)) { ?>
                              <li class="label label--primary-light"><?= _htmlspecialchars($word) ?>
                              <i aria-hidden="true" role="img" class="fi-x"></i></li>
                          <?php }
                              } ?>
                        </ul>
                      </div>

                      <p><?= gettext('We have also found the following related terms. Pick the ones you’d like to include alert?') ?></p>

                      <fieldset>
                        <legend><?= gettext('Related Terms') ?></legend>
                        <div>
                        <?php foreach ($suggestions as $suggestion) { ?>
                          <input type="hidden" name="related_terms[]" value="<?= _htmlspecialchars($suggestion) ?>">
                          <label><input type="checkbox" name="selected_related_terms[]" value="<?= _htmlspecialchars($suggestion) ?>"<?= in_array($suggestion, $selected_related_terms) ? ' checked' : '' ?>><?= _htmlspecialchars($suggestion) ?></label><br>
                        <?php } ?>
                          <label><input type="checkbox" name="add_all_related" id="add-all"<?= $add_all_related == 'on' ? ' checked' : '' ?>><?= gettext('Add all related terms') ?></label>
                        </div>
                      </fieldset>

                      <dl class="alert-meta-info">
                        <?php if ($search_result_count > 0) { ?>
                          <div class="content-header-item">
                            <dt><?= gettext('This week') ?></dt>
                            <dd><?= sprintf(gettext('%d mentions'), $search_result_count) ?></dd>
                          </div>
                        <?php } ?>

                        <?php if (isset($lastmention)) { ?>
                          <div class="content-header-item">
                          <dt><?= gettext('Date of last mention') ?></dt>
                            <dd>30 May 2024</dd>
                          </div>
                        <?php } ?>

                        <a href="/search/?q=<?= _htmlspecialchars($criteria) ?>" target="_blank" class="button small"><?= gettext('See results for this alert') ?></a>
                      </dl>

                      <button type="submit" name="step" value="define" class="prev" aria-label="Go back to Step 2">Previous</button>
                      <button type="submit" name="step" value="review" class="next" aria-label="Go to Step 3">Next</button>
                    </div>
                  <?php } elseif ($step == "review") { ?>

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
                    <!-- Step 4 (Review) -->
                    <div class="alert-step" id="step3" role="region" aria-labelledby="step3-header">
                      <h2 id="step3-header"><?= gettext('Review Your Alert') ?></h2>

                      <div class="keyword-list alert-page-subsection">
                        <h3 class="heading-with-bold-word"><?= gettext('You will get an alert if any of these words are in a speech') ?>:</h3>
                        <ul>
                          <?php foreach ($keywords as $word) { ?>
                          <li class="label label--primary-light"><?= _htmlspecialchars($word) ?>
                            <i aria-hidden="true" role="img" class="fi-x"></i></li>
                          <?php } ?>
                        </ul>
                      </div>

                      <?php if ($exclusions) { ?>
                      <div class="keyword-list excluded-keywords alert-page-subsection">
                      <h3 class="heading-with-bold-word"><?= gettext('Unless the speech also includes these words') ?>:</h3>
                        <ul>
                          <?php foreach (explode(" ", $exclusions) as $word) { ?>
                          <li class="label label--red"><?= _htmlspecialchars($word) ?>
                            <i aria-hidden="true" role="img" class="fi-x"></i></li>
                          <?php } ?>
                        </ul>
                      </div>
                      <?php } ?>

                      <div class="keyword-list alert-page-subsection">
                      <?php if (count($sections) > 0) { ?>
                        <h3 class="heading-with-bold-word"><?= gettext('And only if the speech is in') ?>:</h3>
                          <ul>
                          <?php foreach ($sections as $word) { ?>
                            <li class="label label--red"><?= _htmlspecialchars($word) ?>
                              <i aria-hidden="true" role="img" class="fi-x"></i></li>
                          <?php } ?>
                          </ul>
                      <?php } else { ?>
                        <h3 class="heading-with-bold-word"><?= gettext('in the UK, Scottish or Welsh Parliaments or Northern Ireland Assembly') ?></h3>
                      <?php } ?>
                      </div>

                      <?php if (count($members) > 0) { ?>
                      <div class="keyword-list alert-page-subsection">
                      <h3 class="heading-with-bold-word"><?= gettext('And only when spoken by') ?></h3>
                        <ul>
                          <?php foreach ($members as $member) { ?>
                          <li class="label label--primary-light"><?= $member['given_name'] ?> <?= $member['family_name'] ?>
                            <i aria-hidden="true" role="img" class="fi-x"></i></li>
                          <?php } ?>
                        </ul>
                      </div>
                      <?php } ?>

                      <dl class="alert-meta-info">
                        <?php if ($search_result_count > 0) { ?>
                          <div class="content-header-item">
                            <dt><?= gettext('This week') ?></dt>
                            <dd><?= sprintf(gettext('%d mentions'), $search_result_count) ?></dd>
                          </div>
                        <?php } ?>

                        <?php if (isset($lastmention)) { ?>
                          <div class="content-header-item">
                          <dt><?= gettext('Date of last mention') ?></dt>
                            <dd>30 May 2024</dd>
                          </div>
                        <?php } ?>

                        <a href="/search/?q=<?= _htmlspecialchars($criteria) ?>" target="_blank" class="button small"><?= gettext('See results for this alert') ?></a>
                      </dl>

                      <button type="submit" name="step" value="define" class="prev" aria-label="Go back to Step 2">Go Back</button>
                      <button class="button" type="submit" name="step" value="confirm">
                      <span><?= gettext('Save alert') ?></span>
                        <i aria-hidden="true" class="fi-save"></i>
                      </button>
                      <button type="submit" class="button red" name="action" value="Abandon">
                        <span><?= gettext('Abandon changes') ?></span>
                        <i aria-hidden="true" class="fi-trash"></i>
                      </button>
                      <?php if ($token) { ?>
                      <button type="submit" class="button red" name="action" value="Delete">
                        <span><?= gettext('Delete alert') ?></span>
                        <i aria-hidden="true" class="fi-trash"></i>
                      </button>
                      <?php } ?>
                    </div>
                    <?php } ?>
                  </form>

                </div>
              </div>
            </div>
          </div>