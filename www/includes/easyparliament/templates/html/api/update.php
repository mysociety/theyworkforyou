<?php

$stripe = $subscription->stripe;
$stripeToken = get_http_var('stripeToken');
$payment_method = get_http_var('payment_method');
$charitable_tick = $stripe ? $stripe->discount : '';
$charitable = $stripe ? $stripe->metadata['charitable'] : '';
$charity_number = $stripe ? $stripe->metadata['charity_number'] : '';
$description = $stripe ? $stripe->metadata['description'] : '';

function rdio($name, $value, $text, $id, $required = false, $checked = false) {
    ?>
    <li><label for="<?= $id ?>"><input type="radio" name="<?= $name ?>" value="<?= $value ?>" id="<?= $id ?>"
    <?= $required ? 'required' : '' ?>
    <?= $value == $checked ? 'checked' : '' ?>>
    <?= $text ?></label></li>
<?php
}

?>

<h2><?= $stripe ? "Change plan" : "Subscribe to a plan" ?></h2>

<?php if ($errors && $stripeToken) { ?>
  <p class="account-form__errors">
    We have safely stored your payment information, and you have not yet been charged.
    Please correct the issues below, you will not need to reenter your card details.
  </p>
<?php } ?>

<?php
if ($errors) {
    print "<ul class='account-form__errors'>";
    foreach ($errors as $error) {
        print "<li>$error</li>";
    }
    print "</ul>";
}
?>

<?php if ($stripe && $stripe->cancel_at_period_end) { ?>
<p>Your plan is curently set to expire on <?= date('d/m/Y', $stripe->current_period_end) ?>.
If you update your plan below, it will be reactivated.
</p>
<?php } ?>

<form id="signup_form" method="post" action="/api/update-plan" autocapitalize="off">
    <?= \Volnix\CSRF\CSRF::getHiddenInputString() ?>


<div class="row">
    <label for="id_plan_0">Please choose a plan (all prices include VAT):</label>
    <ul id="id_plan">
        <?php
        $plan = $stripe ? $stripe->plan->id : get_http_var('plan');
rdio('plan', 'twfy-1k', '£20/mth – 1,000 calls per month', 'id_plan_0', 1, $plan);
rdio('plan', 'twfy-5k', '£50/mth – 5,000 calls per month', 'id_plan_1', 1, $plan);
rdio('plan', 'twfy-10k', '£100/mth – 10,000 calls per month', 'id_plan_2', 1, $plan);
rdio('plan', 'twfy-0k', '£300/mth – Unlimited calls', 'id_plan_3', 1, $plan);
?>
    </ul>
</div>

<div class="row">
    <label>
        <input type="checkbox" name="charitable_tick" id="id_charitable_tick"<?php if ($charitable_tick) {
            echo ' checked';
        } ?>>
        I qualify for a charitable discounted price
    </label>
</div>

<div id="charitable-qns"<?php if (!$charitable_tick) {
    echo ' style="display:none"';
} ?>>
    <div class="row">
        <label for="id_charitable_0">Are you?</label>
        <ul id="id_charitable">
            <?php
            rdio('charitable', 'c', 'Registered charity', 'id_charitable_0', 0, $charitable);
rdio('charitable', 'i', 'Individual pursuing a non-profit project on an unpaid basis', 'id_charitable_1', 0, $charitable);
rdio('charitable', 'o', 'Neither', 'id_charitable_2', 0, $charitable);
?>
        </ul>
    </div>

    <div id="charity-number"<?php if (!$stripe || !$charity_number) {
        echo ' style="display:none"';
    } ?>>
        <div class="row">
            <label for="id_charity_number">If charity, please provide your registered charity number:</label>
            <input type="text" name="charity_number" id="id_charity_number" maxlength="500" value="<?=_htmlentities($charity_number) ?>">
        </div>
    </div>

    <div id="charitable-desc"<?php if (!$stripe || !$description) {
        echo ' style="display:none"';
    } ?>>
        <div class="row">
            <label for="id_description">If an individual, please provide details of your project:</label>
            <input type="text" name="description" id="id_description" maxlength="500" value="<?=_htmlentities($description) ?>">
        </div>
    </div>

    <p id="charitable-neither" style="display:none" class="error">
        Sorry, you don’t qualify for a charitable discounted price; you should untick that box.
    </p>

</div>

<?php if (!$stripe) { ?>
<div class="row">
    <label>
        <input type="checkbox" name="tandcs_tick" id="id_tandcs_tick">
        I agree to the <a href="/api/terms" target="_blank">terms and conditions</a>
    </label>
</div>
<?php } ?>

    <input type="hidden" name="stripeToken" id="id_stripeToken" value="<?=_htmlspecialchars($stripeToken) ?>">
    <input type="hidden" name="payment_method" id="id_payment_method" value="<?=_htmlspecialchars($payment_method) ?>">


    <noscript>
        <p class="account-form__errors"> Unfortunately, our payment
        processor requires JavaScript to take payment.<br>Please
        <a href="/contact/">contact us</a> about your requirements and
        we’ll be happy to help.</p>
    </noscript>

<?php if (!$stripeToken) { ?>
    <div id="js-payment-needed">
        <div class="row">
            <label for="id_card_name">Name on card:</label>
            <input type="text" name="card_name" id="id_card_name" value="">
        </div>
        <div class="row">
            <label for="card-element">Credit or debit card details:</label>
            <div id="card-element"><!-- Stripe Element will be inserted here --></div>
            <div id="card-errors" role="alert"></div>
        </div>
    </div>
<?php } ?>

    <div class="row row--submit">
        <button id="customButton" class="button">
            <?= $stripe ? "Change plan" : "Add plan" ?></h2>
        </button>
        <div id="spinner" class="mysoc-spinner mysoc-spinner--small" role="status">
            <span class="sr-only">Processing...</span>
        </div>
    </div>
</form>

<script src="https://js.stripe.com/v3"></script>
<script id="js-payment"
    <?php if ($subscription->has_payment_data) {
        echo 'data-has-payment-data="1"';
    } ?>
    <?php if ($stripe) {
        echo 'data-has-subscription="1"';
    } ?>
    data-key="<?= STRIPE_PUBLIC_KEY ?>" data-api-version="<?= STRIPE_API_VERSION ?>"
    src="<?= cache_version('js/payment.js') ?>"></script>
