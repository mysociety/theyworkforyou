<div class="full-page legacy-page static-page"> <div class="full-page__row"> <div class="panel">        <div class="stripe-side">
                <?php if (isset($errors['firstname'])) { ?>
                <p class="error">
                    <?= $errors['firstname'] ?>
                </p>
                <?php } ?>

            <div class="main">
				<h1><?= gettext('Join TheyWorkForYou') ?></h1>

        <p><?= gettext('Joining TheyWorkForYou makes it easier to manage your email alerts…') ?></p>
        <p><?= gettext('Already joined? <a href="/user/login/">Then sign in!</a>') ?></p>

                <form method="post" class="join-form" action="/user/index.php">
                <?php if (isset($errors['firstname'])) { ?>
                <p class="error">
                    <?= $errors['firstname'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="firstname"><?= gettext('Your first name:') ?></label></span>
                <span class="formw"><input type="text" name="firstname" id="firstname" class="form-control" value="<?= isset($firstname) ? _htmlentities($firstname) : '' ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['lastname'])) { ?>
                <p class="error">
                    <?= $errors['lastname'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="lastname"><?= gettext('Your last name:') ?></label></span>
                <span class="formw"><input type="text" name="lastname" id="lastname" class="form-control" value="<?= isset($lastname) ? _htmlentities($lastname) : '' ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['email'])) { ?>
                <p class="error">
                    <?= $errors['email'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="em"><?= gettext('Email address:') ?></label></span>
                <span class="formw"><input type="email" name="em" id="em" class="form-control" value="<?= isset($email) ? _htmlentities($email) : '' ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['password'])) { ?>
                <p class="error">
                    <?= $errors['password'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="password"><?= gettext('Password:') ?></label></span>
                <span class="formw"><input type="password" name="password" id="password" class="form-control" value="" maxlength="30" size="20" class="form"> <small><?= gettext('At least six characters') ?></small></span>
                </div>

                <?php if (isset($errors['password2'])) { ?>
                <p class="error">
                    <?= $errors['password2'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="password2"><?= gettext('Repeat password:') ?></label></span>
                <span class="formw"><input type="password" name="password2" id="password2" class="form-control" value="" maxlength="30" size="20" class="form"></span>
                </div>

                <?php if (isset($errors['postcode'])) { ?>
                <p class="error">
                    <?= $errors['postcode'] ?>
                </p>
                <?php } ?>

                <br style="clear: left;">&nbsp;<br>
                <div class="row">
                <span class="label"><label for="postcode"><?= gettext('Your UK postcode:') ?></label></span>
                <span class="formw"><input type="text" name="postcode" id="postcode" class="form-control" value="<?= isset($postcode) ? _htmlentities($postcode) : '' ?>" maxlength="10" size="10" class="form"> <small><?= gettext('Optional and not public') ?></small></span>
                </div>

                <?php if (isset($errors['url'])) { ?>
                <p class="error">
                    <?= $errors['url'] ?>
                </p>
                <?php } ?>

                <div class="row">
                &nbsp;<br><?= gettext("Do you want to receive the monthly newsletter from mySociety, with news on TheyWorkForYou and our other projects?") ?>
                </div>

                <?php if (isset($errors['optin'])) { ?>
                <p class="error">
                    <?= $errors['optin'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="formw"><input type="radio" name="optin" id="optintrue" value="true" <?= isset($optin) && $optin == 'Yes' ? ' checked' : '' ?>> <label for="optintrue">Yes</label><br>
                <input type="radio" name="optin" id="optinfalse" value="false" <?= isset($optin) && $optin == 'No' ? ' checked' : !isset($optin) ? ' checked' : '' ?>> <label for="optinfalse">No</label></span>
                </div>

                <div class="row">
                <?= gettext("Would you like to receive email updates on your MP&rsquo;s activity in Parliament?") ?>
                <br><small><?= gettext("(if you&rsquo;re already getting email alerts to your address, don&rsquo;t worry about this)") ?></small>
                </div>

                <div class="row">
                <span class="formw"><input type="radio" name="mp_alert" id="mp_alerttrue" value="true" <?= isset($mp_alert) && $mp_alert == 'Yes' ? ' checked' : '' ?>> <label for="mp_alerttrue">Yes</label><br>
                <input type="radio" name="mp_alert" id="mp_alertfalse" value="false" <?= isset($mp_alert) && $mp_alert == 'No' ? ' checked' : !isset($mp_alert) ? ' checked' : '' ?>> <label for="mp_alertfalse">No</label></span>
                </div>

                <div class="row">
                <span class="formw"><input type="submit" class="button" value="<?= gettext("Join TheyWorkForYou") ?>"></span>
                </div>

                <input type="hidden" name="submitted" value="true">

              <?php if (isset($ret)) { ?>
                <input type="hidden" name="ret" value="<?= _htmlentities($ret) ?>">
              <?php } ?>
                <input type="hidden" name="pg" value="join">

                </form>
            </div> <!-- end .main -->
            <div class="sidebar">
              <div class="block" id="help">


              </div>
            </div> 
          </div> <!-- end .sidebar -->
          <div class="break"></div>
        </div>
        </div>
    </div>
</div>
