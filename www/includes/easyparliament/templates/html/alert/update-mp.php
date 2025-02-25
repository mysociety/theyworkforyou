<div class="full-page alerts-header alerts-header--jumbo">
    <div class="full-page__row">
        <div class="full-page__unit">

            <h1>Update your MP</h1>

          <?php if (isset($data['already_signed_up'])) { ?>
            <div class="alerts-message alerts-message--reminder">
                <h2>You are already signed up</h2>
                <p>You are already receiving alerts when your new MP, <?= $data['mp_name'] ?>, speaks in Parliament or receives an answer to a written question.</p>
                <?php if (isset($data['user_signed_in'])) { ?>
                <p><a href="/alert/" class="button radius">Show my email settings</a></p>
                <?php } ?>
            </div>
          <?php } elseif (isset($data['update'])) { ?>

            <div class="alerts-message alerts-message--confirmation-sent">
                <h2>Confirm your update!</h2>
                <p>Please confirm that you want to update your alerts from <?= $data['old_mp'] ?> to <?= $data['new_mp'] ?></p>
                <form class="alerts-form" action="/alert/update-mp/" method="post">
                <input type="hidden" name="update-alert" value="1">
                <input type="hidden" name="confirmation" value="<?= $data['confirmation'] ?>">
                <p><input type="submit" class="button radius" value="Update"></p>
                </form>
            </div>

          <?php } elseif (isset($data['signedup_no_confirm']) || isset($data['confirmation_received'])) { ?>

            <div class="alerts-message alerts-message--confirmation-received">
                <h2>Thanks for subscribing!</h2>
                <p>You will now receive alerts when your MP speaks in Parliament or receives an answer to a written question.</p>
                <?php if (isset($data['user_signed_in'])) { ?>
                <p><a href="/alert/" class="button radius">Show my email settings</a></p>
                <?php } ?>
            </div>

          <?php } elseif (isset($data['error'])) { ?>
            <div class="alerts-message alerts-message--error">
                <h2>Something went wrong</h2>
                <p>Sorry, we were unable to create this alert. Please try again <a href="/alert/by-postcode/">using your postcode</a>. Thanks.</p>
            </div>

          <?php } else { ?>

            <p class="lead">Enter your postcode, and we&rsquo;ll email you every time your MP speaks or receives a written answer.</p>

            <form class="alerts-form" action="/alerts/by-postcode/" method="post">
                <input type="hidden" name="add-alert" value="1">
                <p>
                    <label for="id_postcode">Your postcode</label>
                  <?php if (isset($data['postcode'])) { ?>
                    <input type="text" name="postcode" id="id_postcode" value="<?= _htmlentities($data['postcode']) ?>">
                  <?php } else { ?>
                    <input type="text" name="postcode" id="id_postcode">
                  <?php } ?>
                </p>
                <p>
                    <label for="id_email">Your email address</label>
                  <?php if (isset($data['email'])) { ?>
                    <input type="text" name="email" id="id_email" value="<?= _htmlentities($data['email']) ?>">
                  <?php } else { ?>
                    <input type="text" name="email" id="id_email">
                  <?php } ?>
                </p>
                <p>
                    <button type="submit" class="button radius">Set up alerts</button>
                </p>
            </form>

          <?php } ?>

        </div>
    </div>
</div>
