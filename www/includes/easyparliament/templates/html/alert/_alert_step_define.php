                <input type="hidden" name="this_step" value="define">
                <div class="alert-step" id="step1" role="region" aria-labelledby="step1-header">
                <h2 id="step1-header"><?= gettext('Keyword alert') ?></h2>

                  <?php if (!$email_verified) { ?>
                  <div class="alert-form__section">
                    <?php if (isset($errors['email'])) { ?>
                      <span class="alert-page-error"><?= $errors['email'] ?></span>
                    <?php } ?>
                    <label for="email"><?= gettext('Your email address') ?></label>
                    <input type="email" class="form-control" placeholder="<?= gettext('Your email address') ?>" name="email" id="email" value="<?= _htmlentities($email) ?>">
                  </div>
                  <?php } ?>

                  <div class="alert-form__section">
                  <label for="words[]"><?= gettext('What word or phrase would you like to receive alerts about? (add as many as you like)') ?></label>
                    <?php if (isset($errors['alertsearch']) && $submitted) { ?>
                      <span class="alert-page-error"><?= $errors['alertsearch'] ?></span>
                    <?php } ?>
                    <input type="text" id="words0" name="words[]" aria-required="true" value="<?= count($keywords) > 0 ? _htmlspecialchars($keywords[0]) : '' ?>" placeholder="e.g. Freedom of Information, FOI">
                    <?php foreach (array_slice($keywords, 1) as $index => $word) { ?>
                      <input type="text" id="words<?= $index + 1 ?>" name="words[]" value="<?= _htmlspecialchars($word) ?>" placeholder="e.g. Freedom of Information, FOI">
                    <?php } ?>
                    <?php if ($addword) { ?>
                    <input type="text" id="words<?= count($words) ?>" name="words[]" value="" placeholder="e.g. Freedom of Information, FOI">
                    <?php } ?>
                    <button class="button" type="submit" name="addword" value="add">
                      <i aria-hidden="true" class="fi-save"></i>
                      <span><?= gettext('Add another phrase') ?></span>
                    </button>
                  </div>

                  <div class="alert-form__section">
                    <div class="checkbox-wrapper">
                      <input type="checkbox" id="match_all" name="match_all"<?= $match_all ? ' checked' : ''?>>
                      <label for="match_all"><?= gettext('Require all phrases to be present (AND rather than OR)') ?></label>
                    </div>
                  </div>
                  <hr />
                  <div class="alert-form__section">
                    <label for="exclusions"><?= gettext('Are there any phrases you would not like to receive alerts about? (optional)') ?></label>
                    <input type="text" id="exclusions" name="exclusions" aria-required="true" value="<?= _htmlspecialchars($exclusions) ?>" placeholder="e.g. Information Rights">
                  </div>

                  <div class="alert-form__section">
                  <label for="select-section"><?= gettext('Would you like to limit which debates/questions we alert about?') ?></label>
                    <select name="search_section" id="select-section">
                    <option value=""><?= gettext('Include all') ?></option>
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

                    <div class="alert-form__section">
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
                          <label class="alert-form__label" for="representative_<?= $index ?>"><?= $name ?></label><br>
                        <?php } ?>
                      <?php } ?>
                      <p><?= gettext("Or edit the name") ?></p>
                    <?php } ?>
                      <input type="text" id="representative" name="representative" value="<?= _htmlspecialchars($representative) ?>" aria-required="true">

                  </div>

                  <?php include('_alert_form_abandon_button.php') ?>
                  <button type="submit" name="step" value="review" class="next" aria-label="Go to Step 2">
                    <span><?= gettext('Next →') ?></span>
                  </button>

                </div>
