          <div class="alert-section">
            <div class="alert-section__primary">
              <h1><?php if ($token) { ?>
                <?= gettext('Edit Alert') ?>
              <?php } else { ?>
                <?= gettext('Create Alert') ?>
              <?php } ?>
              </h1>

              <form action="<?= $actionurl ?>" method="POST" class="alerts-form">
                <input type="hidden" name="t" value="<?= _htmlspecialchars($token) ?>">
                <?php if (!$step or $step == "define") { 
                  include("_alert_step_define.php");
                } elseif ($step == "add_vector_related") {
                  include("_alert_step_vector.php");
                } elseif ($step == "review") {
                  include("_alert_step_review.php");
                } ?>
              </form>
            </div>

            <div class="alert-section__secondary">
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
            </div>
          </div>
