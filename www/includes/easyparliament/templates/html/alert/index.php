<div class="full-page">
    <div class="full-page__row">

      <?php if ($message) { ?>
        <div class="alert-section alert-section--feedback">
            <div class="alert-section__primary">
                <h3><?= $message['title'] ?></h3>
                <p class="alert-results">
                    <?= $message['text'] ?>
                </p>
            </div>
        </div>
      <?php } ?>

      <?php if ($results) { ?>
        <div class="alert-section alert-section--feedback">
            <div class="alert-section__primary">
              <?php if ($results == 'alert-confirmed') { ?>
                <h3><?= gettext('Your alert has been confirmed') ?></h3>
                <p>
                    <?= gettext('You will now receive email alerts for the following criteria:') ?>
                </p>
                <ul><?= _htmlspecialchars($criteria) ?></ul>
                <p>
                    <?= gettext('This is normally the day after, but could conceivably be later due to issues at our or the parliament’s end.') ?>
                </p>

                <!-- Google Code for TWFY Alert Signup Conversion Page -->
                <script type="text/javascript">
                /* <![CDATA[ */
                var google_conversion_id = 1067468161;
                var google_conversion_language = "en";
                var google_conversion_format = "3";
                var google_conversion_color = "ffffff";
                var google_conversion_label = "HEeDCOvPjmQQgYuB_QM";
                var google_remarketing_only = false;
                /* ]]> */
                </script>
                <script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
                </script>
                <noscript>
                <div style="display:inline;">
                <img height="1" width="1" style="border-style:none;" alt="" src="//www.googleadservices.com/pagead/conversion/1067468161/?label=HEeDCOvPjmQQgYuB_QM&amp;guid=ON&amp;script=0"/>
                </div>
                </noscript>

              <?php } elseif ($results == 'alert-suspended') { ?>
                <h3><?= gettext('Alert suspended') ?></h3>
                <p>
                    <?= gettext('You can reactivate the alert at any time, from the sidebar below.') ?>
                </p>

              <?php } elseif ($results == 'alert-resumed') { ?>
                <h3><?= gettext('Alert resumed') ?></h3>
                <p>
                    <?= gettext('You will now receive email alerts on any day when there are entries in Hansard that match your criteria.') ?>
                </p>

              <?php } elseif ($results == 'alert-deleted') { ?>
                <h3><?= gettext('Alert deleted') ?></h3>
                <p>
                    <?= gettext('You will no longer receive this alert.') ?>
                </p>

              <?php } elseif ($results == 'all-alerts-deleted') { ?>
                <h3><?= gettext('All alerts deleted') ?></h3>
                <p>
                    <?= gettext('You will no longer receive any alerts.') ?>
                </p>

              <?php } elseif ($results == 'alert-fail') { ?>
                <h3><?= gettext('Hmmm, something’s not right') ?></h3>
                <p>
                    <?= gettext('The link you followed to reach this page appears to be incomplete.') ?>
                </p>
                <p>
                    <?= gettext('If you clicked a link in your alert email you may need to manually copy and paste the entire link to the ‘Location’ bar of the web browser and try again.') ?>
                </p>
                <p>
                    <?= sprintf(gettext('If you still get this message, please do <a href="mailto:%s">email us</a> and let us know, and we’ll help out!'), str_replace('@', '&#64;', CONTACTEMAIL)) ?>
                </p>

              <?php } elseif ($results == 'alert-added') { ?>
                <h3><?= gettext('Your alert has been added') ?></h3>
                <p>
                    <?= sprintf(gettext('You will now receive email alerts on any day when %s in parliament.'), _htmlspecialchars($criteria)) ?>
                </p>

              <?php } elseif ($results == 'alert-confirmation') { ?>
                <h3><?= gettext('We’re nearly done…') ?></h3>
                <p>
                    <?= gettext('You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address and receive future alerts. Thanks.') ?>
                </p>

              <?php } elseif ($results == 'alert-exists') { ?>
                <h3><?= gettext('You’re already subscribed to that!') ?></h3>
                <p>
                    <?= gettext('It’s good to know you’re keen though.') ?>
                </p>

              <?php } elseif ($results == 'alert-already-signed') { ?>
                <h3><?= gettext('We’re nearly done') ?></h3>
                <p>
                    <?= gettext('You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address and receive future alerts. Thanks.') ?>
                </p>

              <?php } elseif ($results == 'alert-fail') { ?>
                <h3><?= gettext('Alert could not be created') ?></h3>
                <p>
                    <?= sprintf(gettext('Sorry, we were unable to create that alert. Please <a href="mailto:%s">let us know</a>. Thanks.'), str_replace('@', '&#64;', CONTACTEMAIL)) ?>
                </p>

              <?php } ?>
            </div>
        </div>
      <?php } ?>

      <?php
          if(
              $members ||
              (isset($constituencies) && count($constituencies) > 0) ||
              ($alertsearch)
          ) {
              /* We need to disambiguate the user's instructions */
              $member_options = false;
              ?>
        <div class="alert-section alert-section--disambiguation">
            <div class="alert-section__primary">

              <?php if ($members) {
                  $member_options = true; ?>
                <h3><?= sprintf(gettext('Sign up for alerts when people matching <i>%s</i> speaks'), _htmlspecialchars($alertsearch)) ?></h3>
                <ul>
                  <?php
                    foreach ($members as $row) {
                        ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="pid" value="<?= $row['person_id'] ?>">
                            <?php
                                  $name = member_full_name($row['house'], $row['title'], $row['given_name'], $row['family_name'], $row['lordofname']);
                        if ($row['constituency']) {
                            $name .= ' (' . gettext($row['constituency']) . ')';
                        }
                        printf(gettext('When %s speaks.'), $name);
                        ?>
                            <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>"></form>
                        </form>
                    </li>
                  <?php } ?>
                </ul>
              <?php } ?>

              <?php if (isset($constituencies) && count($constituencies) > 0) {
                  $member_options = true; ?>
                <h3><?= sprintf(gettext('Sign up for alerts when MPs for constituencies matching <i>%s</i> speaks'), _htmlspecialchars($alertsearch)) ?></h3>
                <ul>
                <?php foreach ($constituencies as $constituency => $member) { ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="pid" value="<?= $member->person_id() ?>">
                            <?= $member->full_name() ?>
                            (<?= _htmlspecialchars($constituency) ?>)
                            <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>"></form>
                        </li>
                <?php } ?>
                </ul>
              <?php } ?>

              <?php if ($alertsearch) {
                  if ($member_options) { ?>
                <h3><?= gettext('Sign up for alerts for topics') ?></h3>
                <?php } else { ?>
                <h3><?= gettext('Great! Can you just confirm what you mean?') ?></h3>
                <?php } ?>
                <ul>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="keyword" value="<?= _htmlspecialchars($alertsearch) ?>">
                            <?= sprintf(gettext('Receive alerts when %s'), _htmlspecialchars($alertsearch_pretty)) ?>
                            <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>">
                        </form>
                      <?php if (isset($mistakes['multiple'])) { ?>
                        <em class="error"><?= gettext('
                            You have used a comma in your search term –
                            are you sure this is what you want? You cannot
                            sign up to multiple search terms using a comma
                            – either use OR, or create a separate alert
                            for each individual term.') ?>
                        </em>
                      <?php } ?>
                      <?php if (isset($mistakes['postcode_and'])) { ?>
                        <em class="error"><?= gettext('
                            You have used a postcode and something else in your
                            search term – are you sure this is what you
                            want? You will only get an alert if all of these
                            are mentioned in the same debate.') ?>
                          <?php if (isset($member_alertsearch)) {
                              printf(gettext('Did you mean to get alerts for when your representative mentions something instead? If so maybe you want to subscribe to…'));
                          } ?>
                        </em>
                      <?php } ?>
                    </li>

                  <?php if (isset($member_alertsearch)) { ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="keyword" value="<?= _htmlspecialchars($member_alertsearch) ?>">
                            <?= sprintf(gettext('Mentions of [%s] by your MP, %s'), _htmlspecialchars($member_displaysearch), $member->full_name()) ?>
                            <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>">
                        </form>
                    </li>
                  <?php } ?>

                  <?php if (isset($scottish_alertsearch)) { ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="keyword" value="<?= _htmlspecialchars($scottish_alertsearch) ?>">
                            <?= sprintf(gettext('Mentions of [%s] by your MSP, %s'), _htmlspecialchars($member_displaysearch), $scottish_member->full_name()) ?>
                            <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>">
                        </form>
                    </li>
                  <?php } ?>

                  <?php if (isset($welsh_alertsearch)) { ?>
                    <li>
                        <form action="<?= $actionurl ?>" method="post">
                            <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                            <input type="hidden" name="email" value="<?= _htmlspecialchars($email) ?>">
                            <input type="hidden" name="keyword" value="<?= _htmlspecialchars($welsh_alertsearch) ?>">
                            <?= sprintf(gettext('Mentions of [%s] by your MS, %s'), _htmlspecialchars($member_displaysearch), $welsh_member->full_name()) ?>
                            <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>">
                        </form>
                    </li>
                  <?php } ?>
                </ul>
              <?php } ?>
            </div>
        </div>
      <?php } ?>

        <div class="alert-section">
            <div class="alert-section__secondary">
              <?php if ($email_verified) { ?>

                  <?php if ($alerts) { ?>
                    <?php include('_list.php'); ?>
                  <?php } ?>

                  <?php if ($current_mp) { ?>
                    <h3><?= gettext('Your MP alert') ?></h3>
                    <ul class="alerts-manage__list">
                        <li>
                            <?= sprintf(gettext('You are not subscribed to an alert for your current MP, %s'), $current_mp->full_name()) ?>.
                            <form action="<?= $actionurl ?>" method="post">
                                <input type="hidden" name="t" value="<?=_htmlspecialchars($token)?>">
                                <input type="hidden" name="pid" value="<?= $current_mp->person_id() ?>">
                                <input type="submit" class="button small" value="<?= gettext('Subscribe') ?>">
                            </form>
                        </li>
                    </ul>
                  <?php } ?>

              <?php } else { ?>
                <p>
                    <?= sprintf(gettext('If you <a href="%s">join</a> or <a href="%s">sign in</a>, you can suspend, resume and delete your email alerts from your profile page.'), '/user/?pg=join', '/user/login/?ret=%2Falert%2F') ?>
                </p>
                <p>
                    <?= gettext('Plus, you won’t need to confirm your email address for every alert you set.') ?>
                </p>
              <?php } ?>
            </div>

            <div class="alert-section__primary">

              <?php if ($pid) { ?>
                <h3>
                    <?php
                        $name = $pid_member->full_name();
                  if ($pid_member->constituency()) {
                      $name .= ' (' . _htmlspecialchars($pid_member->constituency()) . ')';
                  } ?>
                    <?= sprintf(gettext('Sign up for an alert when %s speaks.'), $name) ?>
                </h3>
              <?php } elseif ($keyword) { ?>
                <h3>
                    <?= sprintf(gettext('Sign up for an alert when %s.'), _htmlspecialchars($display_keyword)) ?>
                </h3>
              <?php } elseif ($alertsearch) { ?>
                <h3><?= gettext('Not quite right? Search again to refine your email alert.') ?></h3>
              <?php } else { ?>
                <h3><?= gettext('Request a new TheyWorkForYou email alert') ?></h3>
              <?php } ?>

                <form action="<?= $actionurl ?>" method="post" class="alert-page-main-inputs">
                  <?php if (!$email_verified) { ?>
                    <p>
                      <?php if (isset($errors["email"]) && $submitted) { ?>
                        <span class="alert-page-error"><?= $errors["email"] ?></span>
                      <?php } ?>
                        <input type="email" class="form-control" placeholder="<?= gettext('Your email address') ?>" name="email" id="email" value="<?= _htmlentities($email) ?>">
                    </p>
                  <?php } ?>

                    <p>
                      <?php if ($pid) { ?>
                        <input type="text" class="form-control" name="alertsearch" id="alertsearch" disabled="disabled"
                            value="<?= $pid_member->full_name() ?><?php if ($pid_member->constituency()) { ?> (<?= _htmlspecialchars($pid_member->constituency()) ?>)<?php } ?>">
                      <?php } elseif ($keyword) { ?>
                        <input type="text" class="form-control" name="alertsearch" id="alertsearch" disabled="disabled" value="<?= _htmlspecialchars($display_keyword) ?>">
                      <?php } else { ?>
                        <input type="text" class="form-control" placeholder="<?= gettext('Search term, postcode, or MP name') ?>" name="alertsearch" id="alertsearch" value="<?= _htmlentities($search_text) ?>">
                      <?php } ?>
                        <input type="submit" class="button" value="<?= ($pid || $keyword) ? gettext('Subscribe') : gettext('Search') ?>">
                    </p>

                    <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                    <input type="hidden" name="submitted" value="1">

                  <?php if ($pid) { ?>
                    <input type="hidden" name="pid" value="<?= _htmlspecialchars($pid) ?>">
                  <?php } ?>
                  <?php if ($keyword) { ?>
                    <input type="hidden" name="keyword" value="<?= _htmlspecialchars($keyword) ?>">
                  <?php } ?>

                  <?php if ($sign) { ?>
                    <input type="hidden" name="sign" value="<?= _htmlspecialchars($sign) ?>">
                  <?php } ?>

                  <?php if ($site) { ?>
                    <input type="hidden" name="site" value="<?= _htmlspecialchars($site) ?>">
                  <?php } ?>
                </form>

              <?php if (!$pid && !$keyword) { ?>
                <div class="alert-page-search-tips">
                    <h3><?= gettext('Search tips') ?></h3>
                    <p>
                        <?= gettext('To be alerted on an exact <strong>phrase</strong>, be sure to put it in quotes. Also use quotes around a word to avoid stemming (where ‘horse’ would also match ‘horses’).') ?>
                    </p>
                    <p>
                        <?= gettext('You should only enter <strong>one term per alert</strong> – if you wish to receive alerts on more than one thing, or for more than one person, simply fill in this form as many times as you need, or use boolean OR.') ?>
                    </p>
                    <p>
                        <?= gettext('For example, if you wish to receive alerts whenever the words <i>horse</i> or <i>pony</i> are mentioned in Parliament, please fill in this form once with the word <i>horse</i> and then again with the word <i>pony</i> (or you can put <i>horse OR pony</i> with the OR in capitals). Do not put <i>horse, pony</i> as that will only sign you up for alerts where <strong>both</strong> horse and pony are mentioned.') ?>
                    </p>
                </div>

                <div class="alert-page-search-tips">

                    <h3><?= gettext('Step by step guides') ?></h3>
                    <p>
                        <?= gettext('The mySociety blog has a number of posts on signing up for and managing alerts:') ?>
                    </p>

                    <ul>
                        <li><a href="https://www.mysociety.org/2014/07/23/want-to-know-what-your-mp-is-saying-subscribe-to-a-theyworkforyou-alert/"><?= gettext('How to sign up for alerts on what your MP is saying') ?></a>.</li>
                        <li><a href="https://www.mysociety.org/2014/09/01/well-send-you-an-email-every-time-your-chosen-word-is-mentioned-in-parliament/"><?= gettext('How to sign up for alerts when your chosen word is mentioned') ?></a>.</li>
                        <li><?= sprintf(gettext('<a href="%s">Managing email alerts</a>, including how to stop or suspend them.'), 'https://www.mysociety.org/2014/09/04/how-to-manage-your-theyworkforyou-alerts/') ?></li>
                    <ul>
                </div>
              <?php } ?>
            </div>

        </div>

    </div>
</div>
