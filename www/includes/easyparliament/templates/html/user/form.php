<div class="full-page legacy-page static-page"> <div class="full-page__row"> <div class="panel">        <div class="stripe-side">
                <?php if (isset($errors['db'])) { ?>
                <p class="error">
                    <?= $errors['db'] ?>
                </p>
                <?php } ?>

            <div class="main">
            <?php if ($facebook_user) { ?>
              <h1>Edit your details</h1>
              <form method="post" class="edit-form" action="/user/index.php">
                <?php if (isset($errors['postcode'])) { ?>
                <p class="error">
                    <?= $errors['postcode'] ?>
                </p>
                <?php } ?>

                <br style="clear: left;">&nbsp;<br>
                <div class="row">
                <span class="label"><label for="postcode"><?= gettext('Your UK postcode:') ?></label></span>
                <span class="formw"><input type="text" name="postcode" id="postcode" value="<?= _htmlentities($postcode) ?>" maxlength="10" size="10" class="form"> <small><?= gettext('Optional and not public') ?></small></span>
                </div>

                <p>
                <?= gettext('We use this to show you information about your MP.') ?>
                </p>

                <div class="row">
                <span class="formw"><input type="submit" class="submit" value="<?= gettext('Update details') ?>"></span>
                </div>

                <input type="hidden" name="submitted" value="true">

                <input type="hidden" name="pg" value="edit">
              </form>
            <?php } else { ?>
              <?php if (isset($showall) && $showall == true && isset($user_id)) { ?>
              <h1>Edit the user&rsquo;s details</h1>
              <?php } else { ?>
              <h1><?= gettext('Edit your details') ?></h1>
              <?php } ?>

              <form method="post" class="edit-form" action="/user/index.php">
                <?php if (isset($errors['firstname'])) { ?>
                <p class="error">
                    <?= $errors['firstname'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="firstname"><?= gettext('Your first name:') ?></label></span>
                <span class="formw"><input type="text" name="firstname" id="firstname" value="<?= _htmlentities($firstname) ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['lastname'])) { ?>
                <p class="error">
                    <?= $errors['lastname'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="lastname"><?= gettext('Your last name:') ?></label></span>
                <span class="formw"><input type="text" name="lastname" id="lastname" value="<?= _htmlentities($lastname) ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['email'])) { ?>
                <p class="error">
                    <?= $errors['email'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="em"><?= gettext('Email address:') ?></label></span>
                <span class="formw"><input type="text" name="em" id="em" value="<?= _htmlentities($email) ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['password'])) { ?>
                <p class="error">
                    <?= $errors['password'] ?>
                </p>
                <?php } ?>

                <div class="row">
                &nbsp;<br><small><?= gettext('To change your password enter a new one twice below (otherwise, leave blank).') ?></small>
                </div>
                <div class="row">
                <span class="label"><label for="password"><?= gettext('Password:') ?></label></span>
                <span class="formw"><input type="password" name="password" id="password" value="" maxlength="30" size="20" class="form"> <small><?= gettext('At least six characters') ?></small></span>
                </div>

                <?php if (isset($errors['password2'])) { ?>
                <p class="error">
                    <?= $errors['password2'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="password2"><?= gettext('Repeat password:') ?></label></span>
                <span class="formw"><input type="password" name="password2" id="password2" value="" maxlength="30" size="20" class="form"></span>
                </div>

                <?php if (isset($errors['postcode'])) { ?>
                <p class="error">
                    <?= $errors['postcode'] ?>
                </p>
                <?php } ?>

                <br style="clear: left;">&nbsp;<br>
                <div class="row">
                <span class="label"><label for="postcode"><?= gettext('Your UK postcode:') ?></label></span>
                <span class="formw"><input type="text" name="postcode" id="postcode" value="<?= _htmlentities($postcode) ?>" maxlength="10" size="10" class="form"> <small><?= gettext('Optional and not public') ?></small></span>
                </div>

                <?php if (isset($errors['url'])) { ?>
                <p class="error">
                    <?= $errors['url'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="url"><?= gettext('Your website:') ?></label></span>
                <span class="formw"><input type="text" name="url" id="url" value="<?= _htmlentities($url) ?>" maxlength="255" size="20" class="form"> <small><?= gettext('Optional and public') ?></small></span>
                </div>

                <?php if (isset($showall) && $showall == true) { ?>
                  <?php if (isset($errors['status'])) { ?>
                  <p class="error">
                      <?= $errors['status'] ?>
                  </p>
                  <?php } ?>

                  <div class="row">
                  <span class="label">Security status:</span>
                  <span class="formw"><select name="status">
                  <?php
                  foreach ($statuses as $n => $status_name) { ?>
                    <option value="<?= $status_name ?>"<?= $status_name == $status ? ' selected' : '' ?>>
                      <?= $status_name ?>
                    </option>
                  <?php } ?>
                  </select></span>
                  </div>

                  <div class="row">
                  <span class="label"><label for="confirmed">Confirmed?</label></span>
                  <span class="formw"><input type="checkbox" name="confirmed[]" id="confirmed" value="true"
                    <?= isset($confirmed) && $confirmed == true ? 'checked' : '' ?>
                  ></span>
                  </div>

                  <div class="row">
                  <span class="label"><label for="deleted">"Deleted"?</label></span>
                  <span class="formw"><input type="checkbox" name="deleted[]" id="deleted" value="true"
                    <?= isset($deleted) && $deleted == true ? 'checked' : '' ?>
                  > <small>(No data will actually be deleted.)</small></span>
                  </div>

                <?php } ?>


                <?php
                $optin_options = [
                    "optin_service" => gettext("Can we send you occasional emails about TheyWorkForYou.com?"),
                    "optin_stream" => gettext("Do you want to receive our newsletter about our wider democracy work, including our research and campaigns?"),
                    "optin_org" => gettext("Do you want to receive the monthly newsletter from mySociety, with news on TheyWorkForYou and our other projects?"),
                ];

                for ($i = 0; $i < count($optin_options); $i++) {
                    $optin_key = array_keys($optin_options)[$i];
                    $optin_txt = array_values($optin_options)[$i];
                    $optin_value = $$optin_key ?? null;
                    ?>

                <div class="row">
                &nbsp;<br><?= $optin_txt ?>
                </div>

                <?php if (isset($errors[$optin_key])) { ?>
                <p class="error">
                    <?= $errors[$optin_key] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="formw"><input type="radio" name="<?= $optin_key ?>" id="<?= $optin_key ?>true" value="true" <?= $optin_value == 'Yes' ? ' checked' : '' ?>> <label class="option_yesno" for="<?= $optin_key ?>true">Yes</label><br>
                <input type="radio" name="<?= $optin_key ?>" id="<?= $optin_key ?>false" value="false" <?= $optin_value == 'No' ? ' checked' : '' ?>> <label class="option_yesno"  for="<?= $optin_key ?>false">No</label></span>
                </div>

                <?php } ?>

                <div class="row">
                <span class="formw"><input type="submit" class="submit" value="<?= gettext('Update details') ?>"></span>
                </div>

                <input type="hidden" name="submitted" value="true">
                <input type="hidden" name="pg" value="<?= _htmlentities($pg) ?>">

                <?php if (isset($showall) && $showall == true && isset($user_id)) { ?>
                    <input type="hidden" name="u" value="<?= _htmlentities($user_id) ?>">
                <?php } ?>

              </form>
            <?php } ?>
            </div>
            <div class="sidebar">&nbsp;</div>
            <div class="break"></div>
        </div>
     </div>
   </div>
</div>
