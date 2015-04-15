<div class="full-page alerts-header alerts-header--jumbo">
    <div class="full-page__row">
        <div class="full-page__unit">

            <h1>Track your <?php if ($data['recent_election']): ?>new<?php endif ?> MP&rsquo;s parliamentary activity</h1>

          <?php if (isset($data['confirmation_sent'])): ?>

            <div class="alerts-message alerts-message--confirmation-sent">
                <h2>Almost there!</h2>
                <p>We just need to check your email address.</p>
                <p>Please click the link in the email we just sent you.</p>
            </div>

          <?php elseif (isset($data['confirmation_received'])): ?>

            <div class="alerts-message alerts-message--confirmation-received">
                <h2>Thanks for subscribing!</h2>
                <p>You will now receive alerts when your MP speaks in Parliament or receives an answer to a written question.</p>
                <p><a href="#" class="button radius">Show my email settings</a></p>
            </div>

          <?php else: ?>

            <p class="lead">Enter your postcode, and we&rsquo;ll email you every time your MP speaks or receives a written answer.</p>

          <?php if (isset($data['invalid-postcode-or-email'])): ?>
            <div class="alerts-message alerts-message--error">
                <h2>Oops!</h2>
                <p>Please supply a valid UK postcode and email address.</p>
            </div>
          <?php endif ?>

            <form class="alerts-form" method="post">
                <p>
                    <label for="id_postcode">Your postcode</label>
                  <?php if (isset($data['postcode'])): ?>
                    <input type="text" name="postcode" id="id_postcode" value="<?php echo $data['postcode']; ?>">
                  <?php else: ?>
                    <input type="text" name="postcode" id="id_postcode">
                  <?php endif ?>
                </p>
                <p>
                    <label for="id_email">Your email address</label>
                  <?php if (isset($data['email'])): ?>
                    <input type="text" name="email" id="id_email" value="<?php echo $data['email']; ?>">
                  <?php else: ?>
                    <input type="text" name="email" id="id_email">
                  <?php endif ?>
                </p>
                <p>
                    <button type="submit" class="button radius">Set up alerts</button>
                </p>
            </form>

          <?php endif ?>

        </div>
    </div>
</div>
