<div class="full-page legacy-page static-page"> <div class="full-page__row"> <div class="panel">        <div class="stripe-side">
                <?php if (isset($errors['firstname'])) { ?>
                <p class="error">
                    <?= $errors['firstname'] ?>
                </p>
                <?php } ?>

            <div class="main">
				<h1>Join TheyWorkForYou</h1>

        <p>Joining TheyWorkForYou makes it easier to manage your email alerts.</p>
        <p>Already joined? <a href="/user/login/">Then sign in!</a></p>

                <form method="post" action="/user/new_index.php">
                <div class="row">
                <span class="label">Status:</span>
                <span class="formw"></span>
                </div>

                <?php if (isset($errors['firstname'])) { ?>
                <p class="error">
                    <?= $errors['firstname'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="firstname">Your first name:</label></span>
                <span class="formw"><input type="text" name="firstname" id="firstname" value="<?= isset($firstname) ? _htmlentities($firstname) : '' ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['lastname'])) { ?>
                <p class="error">
                    <?= $errors['lastname'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="lastname">Your last name:</label></span>
                <span class="formw"><input type="text" name="lastname" id="lastname" value="<?= isset($lastname) ? _htmlentities($lastname) : '' ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['email'])) { ?>
                <p class="error">
                    <?= $errors['email'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="em">Email address:</label></span>
                <span class="formw"><input type="text" name="em" id="em" value="<?= isset($email) ? _htmlentities($email) : '' ?>" maxlength="255" size="30" class="form"></span>
                </div>

                <?php if (isset($errors['password'])) { ?>
                <p class="error">
                    <?= $errors['password'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="password">Password:</label></span>
                <span class="formw"><input type="password" name="password" id="password" value="" maxlength="30" size="20" class="form"> <small>At least six characters</small></span>
                </div>

                <?php if (isset($errors['password2'])) { ?>
                <p class="error">
                    <?= $errors['password2'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="password2">Repeat password:</label></span>
                <span class="formw"><input type="password" name="password2" id="password2" value="" maxlength="30" size="20" class="form"></span>
                </div>

                <?php if (isset($errors['postcode'])) { ?>
                <p class="error">
                    <?= $errors['postcode'] ?>
                </p>
                <?php } ?>

                <br style="clear: left;">&nbsp;<br>
                <div class="row">
                <span class="label"><label for="postcode">Your UK postcode:</label></span>
                <span class="formw"><input type="text" name="postcode" id="postcode" value="<?= isset($postcode) ? _htmlentities($postcode) : '' ?>" maxlength="10" size="10" class="form"> <small>Optional and not public</small></span>
                </div>

                <?php if (isset($errors['url'])) { ?>
                <p class="error">
                    <?= $errors['url'] ?>
                </p>
                <?php } ?>

                <div class="row">
                <span class="label"><label for="url">Your website:</label></span>
                <span class="formw"><input type="text" name="url" id="url" value="<?= isset($url) ? _htmlentities($url) : '' ?>" maxlength="255" size="20" class="form"> <small>Optional and public</small></span>
                </div>

                <?php if (isset($errors['emailpublic'])) { ?>
                <p class="error">
                    <?= $errors['emailpublic'] ?>
                </p>
                <?php } ?>

                <div class="row">
                &nbsp;<br>Let other users see your email address?
                </div>

                <div class="row">
                <span class="formw"><input type="radio" name="emailpublic" id="emailpublictrue" value="true" <?= isset($emailpublic) && $emailpublic == 'Yes' ? ' checked' : '' ?>> <label for="emailpublictrue">Yes</label><br>
                    <input type="radio" name="emailpublic" id="emailpublicfalse" value="false" <?= isset($emailpublic) && $emailpublic == 'No' ? ' checked' : !isset($emailpublic) ? ' checked' : '' ?>> <label for="emailpublicfalse">No</label></span>
                </div>

                <div class="row">
                &nbsp;<br>Do you wish to receive occasional update emails about TheyWorkForYou.com?
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
                Would you like to receive email updates on your MP&rsquo;s activity in Parliament?
                <br><small>(if you&rsquo;re already getting email alerts to your address, don&rsquo;t worry about this)</small>
                </div>

                <div class="row">
                <span class="formw"><input type="radio" name="mp_alert" id="mp_alerttrue" value="true" <?= isset($mp_alert) && $mp_alert == 'Yes' ? ' checked' : '' ?> <label for="mp_alerttrue">Yes</label><br>
                <input type="radio" name="mp_alert" id="mp_alertfalse" value="false" <?= isset($mp_alert) && $mp_alert == 'No' ? ' checked' : !isset($mp_alert) ? ' checked' : '' ?> <label for="mp_alertfalse">No</label></span>
                </div>

                <div class="row">
                <span class="formw">&nbsp;<br><small>Read our <a href="/houserules/" target="_blank">Terms of Use</a>.</small></span>
                </div>

                <div class="row">
                <span class="formw"><input type="submit" class="submit" value="Join TheyWorkForYou">
                </div>

                <input type="hidden" name="submitted" value="true">

                <input type="hidden" name="pg" value="join">

                </form>
            </div> <!-- end .main -->
            <div class="sidebar">
              <div class="block" id="help">
                <h4>Your privacy</h4>
                <div class="blockbody">

                <p>Welcome to <strong>TheyWorkForYou.com</strong> - the more you contribute and participate, the better the site will get for everyone.</p>

                <p>Our <strong>Privacy Policy</strong> is very simple:</p>

                <p><strong>1.</strong> We guarantee we will not sell or distribute any personal information you share with us</p>
                <p><strong>2.</strong>We will not be sending you unsolicited email</p>
                <p><strong>3.</strong>We will gladly show you the personal data we store about you in order to run the website</p>

                <p>We hope you enjoy using the website.</p>

              </div>
            </div> 
          </div> <!-- end .sidebar -->
          <div class="break"></div>
        </div>
</div>
