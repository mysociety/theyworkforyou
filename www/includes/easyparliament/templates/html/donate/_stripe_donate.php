<?php

# define array of payment amounts and current/one-off options
# note - new annual and monthly payments need to be defined as new prices in stripe
# e.g. 'donate_monthly_10'. 
$payment_amounts = array(
  'monthly' => array(
    '5' => '£5',
    '10' => '£10',
    '25' => '£25',
  ),
  'annually' => array(
    '10' => '£10',
    '50' => '£50',
    '100' => '£100',
  ),
  'one-off' => array(
    '20' => '£20',
    '50' => '£50',
    '100' => '£100',
  ),
);
$default_amounts = array(

  'monthly' => '10',
  'annually' => '10',
  'one-off' => '50',
);

$default_type = 'annually';

# use the how-often parameter if set, if not default to option at end of line (options are 'monthly', 'annually', or 'one-off')
$initial_payment_type = isset($_GET['how-often']) ? $_GET['how-often'] : $default_type;

# use the how-much parameter if set, if not default to default amount for initial payment type
$how_much = isset($_GET['how-much']) ? $_GET['how-much'] : $default_amounts[$initial_payment_type];

# if how-much is not in the allowed values for the current payment type, set to 'other', and set $other_how_much to the value of how-much
if (!array_key_exists($how_much, $payment_amounts[$initial_payment_type])) {
  $how_much = 'other';
  $other_how_much = $_GET['how-much'];
} else {
  $other_how_much = '';
}

?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>


<div class="donation-box">




<div class="grid-row">
    <form class="form donate-form" method="post" name="donation_form">

      <div class="form__error-wrapper">
<?php if (isset($error) and $error) { ?>
      <p class="form__error"><?=$error ?></p>
<?php } ?>
      <noscript>
        <p class="form__error">Unfortunately, our payment
                processor requires JavaScript to take payment.<br>
                Please enable JavaScript and try again, or you can 
                <a href="/contact">contact us</a> for alternative donation methods.</p>
      </noscript>
      </div>
      <h2 class="form__element__heading">Donate to TheyWorkForYou and mySociety</h3>

      <div class="form__element fat-radio-buttons">
      <label for="how-often-once" class="inline-radio-label"><input type="radio" id="how-often-once" name="how-often" value="once" data-default-amount="<?= $default_amounts["one-off"] ?>" required <?php get_checked($initial_payment_type, 'one-off') ?>>One-off donation</label>
      <label for="how-often-annually" class="inline-radio-label"><input type="radio" id="how-often-annually" name="how-often" value="annually" data-default-amount="<?= $default_amounts["annually"] ?>" required <?php get_checked($initial_payment_type, 'annually') ?>>Annual donation</label>
      <label for="how-often-monthly" class="inline-radio-label"><input type="radio" id="how-often-monthly" name="how-often" value="monthly" data-default-amount="<?= $default_amounts["monthly"] ?>" required <?php get_checked($initial_payment_type, 'monthly') ?>>Monthly donation</label>

    </div>


      <div class="form__element fat-radio-buttons donate-amounts">
        <h3 class="form__element__heading">How much would you like to give?</h3>
        <?php foreach ($payment_amounts as $payment_type => $amounts) { ?>
          <?php foreach ($amounts as $amount => $label) { ?>
            <label
             for="how-much-<?=$payment_type?>-<?=$amount?>"
             class="donate-<?=$payment_type?>-amount inline-radio-label"
             <?php if ($payment_type != $initial_payment_type) { ?>style="display:none"<?php } ?>
            >
            <input
             type="radio"
             id="how-much-<?=$payment_type?>-<?=$amount?>"
             name="how-much"
             value="<?=$amount?>" 
             required <?= (($how_much == $amount) and ($payment_type == $initial_payment_type) ) ? ' checked': '' ?>>
            <span class="radio-label-large"><?=$label?></span>
            </label>
          <?php } ?>
        <?php } ?>
        <label for="how-much-other" class="inline-radio-label how-much-other-label">
          <input type="radio" id="how-much-other" name="how-much" value="other" required <?php get_checked($how_much, 'other') ?>>
          <span class="radio-label-large">Other</span>
        </label>
          <div class="how-much-other-value form__element--vertically-stacked fat-radio-buttons__form-inside">
            <label for="how-much-other-value__label">Amount</label>
            <span class="how-much-other-value__currency">£</span>
            <input type="text" id="how-much-other-value__input" class="how-much-other-value__input" name="how-much-other" value="<?= wp_esc_attr($other_how_much) ?>" required>
          </div>
      </div>



      <div class="form__element donate-giftaid">
        <p>If you are a UK tax payer, the value of your donation can be increased by 25% under the Gift Aid scheme <strong>without costing you a penny more</strong>.</p>
        <label for="gift-aid-yes" class="donate-giftaid__label">
            <input type="checkbox" id="gift-aid-yes" name="gift-aid" value="Yes">
            Yes, I want mySociety to claim Gift Aid on my past, present and future donations
            <small>I am a UK tax payer and understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year, it is my responsibility to pay any difference.  
                I will let mySociety know of any changes to my tax status, including changes to my name or address, or if I need to cancel this agreement.</small>
        </label>
      </div>

      <div class="form__element donate-fullname">
        <label for="full-name">Thank you! We need your full first and last name to claim Gift Aid.</label>
        <input type="text" id="full-name" name="full_name" class="form__element__input" placeholder="Your name here">
      </div>

      <div class="form__element fat-radio-buttons donate-contact">
        <p>We&rsquo;d like to thank you for your donation and keep you informed about our work.</p>
        <label class="inline-radio-label" for="contact-yes">
            <input  type="radio" id="contact-yes" name="contact_permission" value="Yes">
            Yes, you can contact me.
        </label>
        <label class="inline-radio-label" for="contact-no">
            <input type="radio" id="contact-no" name="contact_permission" value="No">
            No, you can&rsquo;t contact me.
        </label>
      </div>

      <div class="form__element form__element--submit">
        <div id='recaptcha' class="g-recaptcha" data-sitekey="<?=OPTION_RECAPTCHA_SITE_KEY ?>" data-callback="onDonatePass" data-size="invisible"></div>
        <input type="submit" id="donate_button" value="Donate" class="button button-primary button--large">
        <div id="spinner" class="mysoc-spinner mysoc-spinner--small" role="status">
            <span class="sr-only">Processing…</span>
        </div>
      </div>
      <small>Payment methods avaliable: Card, PayPal, Apple Pay, Google Pay, Direct Debit</small>
      <input type="hidden" name="utm_source" value="<?=htmlspecialchars(get_or_default('utm_source', 'theyworkforyou.com')) ?>">
      <input type="hidden" name="utm_content" value="<?=htmlspecialchars(get_or_default('utm_content')) ?>">
      <input type="hidden" name="utm_medium" value="<?=htmlspecialchars(get_or_default('utm_medium')) ?>">
      <input type="hidden" name="utm_campaign" value="<?=htmlspecialchars(get_or_default('utm_campaign', 'twfy_donate_page')) ?>">
    </form>

</div>
</div>
<script src="https://js.stripe.com/v3"></script>
<script>
var stripe = Stripe('<?=STRIPE_PUBLIC_KEY ?>');
</script>