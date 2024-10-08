<?php

$stripe = $subscription->stripe;

?>

<h2><?= $stripe ? "Change plan" : "Subscribe to a plan" ?></h2>

<?php if ($needs_processing['requires_payment_method']) { ?>

<form id="declined_form" method="post" action="/api/key" autocapitalize="off">
    <?= \Volnix\CSRF\CSRF::getHiddenInputString() ?>

    <div class="account-form__errors">
        Sorry, your card has been declined. Perhaps you can try another?
    </div>

    <?php if ($subscription->stripe->plan) { ?>
        <p>You are subscribing to <strong><?= $subscription->stripe->plan->nickname ?></strong>,

        costing £<?= $subscription->actual_paid ?>/<?= $subscription->stripe->plan->interval ?>.
        <?php if ($subscription->stripe->discount) { ?>
            (£<?= $subscription->stripe->plan->amount ?>/<?= $subscription->stripe->plan->interval ?> with
            <?= $subscription->stripe->discount->coupon->percent_off ?>% discount applied.)
        <?php } ?>
        </p>
    <?php } ?>

    <div class="row">
        <label for="id_card_name">Name on card:</label>
        <div class="account-form__input"><input type="text" name="card_name" required="" id="id_card_name"></div>
    </div>

    <div class="row">
        <label for="card-element">Credit or debit card details:</label>
        <div id="card-element"><!-- A Stripe Element will be inserted here. --></div>
        <div id="card-errors" role="alert"></div>
    </div>

    <input name="payment_method" value="" type="hidden">

    <div class="row row--submit">
        <button id="customButton" class="button">Sign up</button>
        <div id="spinner" class="mysoc-spinner mysoc-spinner--small" role="status">
            <span class="sr-only">Processing…</span>
        </div>
    </div>
</form>
<script src="https://js.stripe.com/v3"></script>
<script id="js-payment" data-key="<?= STRIPE_PUBLIC_KEY ?>" data-api-version="<?= STRIPE_API_VERSION ?>"
    <?php if ($stripe) {
        echo 'data-has-subscription="1"';
    } ?>
    src="<?= cache_version('js/payment.js') ?>"></script>

<?php } elseif ($needs_processing['requires_action']) { ?>

<div align="center">
    <p>Please wait while we authenticate with your bank…</p>
    <div class="mysoc-spinner" role="status">
        <span class="sr-only">Processing…</span>
    </div>
</div>

<script src="https://js.stripe.com/v3"></script>
<script>
    var stripe = Stripe('<?= STRIPE_PUBLIC_KEY ?>', { apiVersion: '<?= STRIPE_API_VERSION ?>' });
    stripe.handleCardPayment('<?= $needs_processing['payment_intent_client_secret'] ?>').then(function(result) {
        location.href = location.href;
    });
</script>

<?php }
