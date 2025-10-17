<?php



?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<a name="donate-form"></a>

<button type="button" id="default_donate_button" class="button button-primary button--large" style="margin-right: 10px" onclick="restrict_to_default('<?= $how_much ?>', '<?= $payment_type ?>')">
        Donate <?= $verbose_amount ?>
</button>

<input type="button" value="Donate another amount" class="open-form-button button button-primary button--large" >
    

<form class="donate-form" method="post" name="donation_form">

    <div class="donate-form__error-wrapper">
        <noscript>
            <p class="donate-form__error">Unfortunately, our payment
            processor requires JavaScript to take payment.<br>
            Please enable JavaScript and try again, or you can
            <a href="/contact">contact us</a> for alternative donation methods.</p>
        </noscript>
    </div>

    <h2>Donate to TheyWorkForYou and mySociety</h3>

    <div class="fat-radio-buttons">
        <label for="how-often-one-off" class="inline-radio-label"><input type="radio" id="how-often-one-off" name="how-often" value="one-off" data-default-amount="<?= $default_amounts["one-off"] ?>" required <?php get_checked($payment_type, 'one-off') ?>>One-off donation</label>
        <label for="how-often-annually" class="inline-radio-label"><input type="radio" id="how-often-annually" name="how-often" value="annually" data-default-amount="<?= $default_amounts["annually"] ?>" required <?php get_checked($payment_type, 'annually') ?>>Annual donation</label>
        <label for="how-often-monthly" class="inline-radio-label"><input type="radio" id="how-often-monthly" name="how-often" value="monthly" data-default-amount="<?= $default_amounts["monthly"] ?>" required <?php get_checked($payment_type, 'monthly') ?>>Monthly donation</label>
    </div>

    <h3>How much would you like to give?</h3>

    <div class="fat-radio-buttons donate-amounts">
      <?php foreach ($payment_amounts as $amount_type => $amounts) { ?>
      <?php foreach ($amounts as $amount => $label) { ?>
        <label
          for="how-much-<?=$amount_type?>-<?=$amount?>"
          class="donate-<?=$amount_type?>-amount inline-radio-label"
          <?php if ($amount_type != $payment_type) { ?>style="display:none"<?php } ?> />
            <input
              type="radio"
              id="how-much-<?=$amount_type?>-<?=$amount?>"
              name="how-much"
              value="<?=$amount?>"
              required <?= (($how_much == $amount) and ($amount_type == $payment_type)) ? ' checked' : '' ?> />
            <span class="radio-label-large"><?=$label?></span>
        </label>
      <?php } ?>
      <?php } ?>
        <label for="how-much-other" class="inline-radio-label how-much-other-label">
            <input type="radio" id="how-much-other" name="how-much" value="other" required <?php get_checked($how_much, 'other') ?> />
            <span class="radio-label-large">Other</span>
        </label>
    </div>

    <div class="how-much-other-value">
        <label for="how-much-other-value">Amount</label>
        <div class="row collapse">
            <div class="small-3 columns">
                <span class="prefix">£</span>
            </div>
            <div class="small-9 columns">
                <input type="text" id="how-much-other-value" class="how-much-other-value__input" name="how-much-other" value="<?= wp_esc_attr($other_how_much) ?>" required />
            </div>
        </div>
    </div>

    <div class="donate-giftaid">
        <p>If you are a UK tax payer, the value of your donation can be increased by 25% under the Gift Aid scheme <strong>without costing you a penny more</strong>.</p>
        <label for="gift-aid-yes" class="donate-giftaid__label">
            <input type="checkbox" id="gift-aid-yes" name="gift-aid" value="Yes">
            Yes, I want mySociety to claim Gift Aid on my past, present and future donations
            <small>I am a UK tax payer and understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year, it is my responsibility to pay any difference. I will let mySociety know of any changes to my tax status, including changes to my name or address, or if I need to cancel this agreement.</small>
        </label>
    </div>

    <div class="donate-fullname">
        <label for="full-name">Thank you! We need your full first and last name to claim Gift Aid.</label>
        <input type="text" id="full-name" name="full_name" placeholder="Your name here">
    </div>

    <p>We&rsquo;d like to thank you for your donation and keep you informed about our work.</p>

    <div class="fat-radio-buttons donate-contact">
        <label class="inline-radio-label" for="contact-yes">
            <input  type="radio" id="contact-yes" name="contact_permission" value="Yes">
            Yes, you can contact me.
        </label>
        <label class="inline-radio-label" for="contact-no">
            <input type="radio" id="contact-no" name="contact_permission" value="No">
            No, you can&rsquo;t contact me.
        </label>
    </div>

    <div class="donate-submit">
      <div id='recaptcha' class="g-recaptcha" data-sitekey="<?=OPTION_RECAPTCHA_SITE_KEY ?>" data-callback="onDonatePass" data-size="invisible"></div>
      <input type="submit" id="donate_button" value="Donate now" class="button button-primary button--large">
      <div id="spinner" class="mysoc-spinner mysoc-spinner--small" role="status">
          <span class="sr-only">Processing…</span>
      </div>
      <p><small>Payment methods available: Card, PayPal, Apple Pay, Google Pay, Direct Debit</small></p>
    </div>

    <input type="hidden" name="utm_source" value="<?=htmlspecialchars(get_http_var('utm_source', 'theyworkforyou.com')) ?>">
    <input type="hidden" name="utm_content" value="<?=htmlspecialchars(get_http_var('utm_content')) ?>">
    <input type="hidden" name="utm_medium" value="<?=htmlspecialchars(get_http_var('utm_medium')) ?>">
    <input type="hidden" name="utm_campaign" value="<?=htmlspecialchars(get_http_var('utm_campaign', 'twfy_donate_page')) ?>">
    
</form>

<script src="https://js.stripe.com/v3"></script>
<script>
var stripe = Stripe('<?=STRIPE_DONATE_PUBLIC_KEY ?>', { apiVersion: '<?= STRIPE_API_VERSION ?>' });
</script>
<script>
    // hide .donate-form by default
    document.querySelector('.donate-form').style.display = 'none';
</script>
