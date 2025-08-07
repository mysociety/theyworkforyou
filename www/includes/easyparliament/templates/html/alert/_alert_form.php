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
          </div>
