<?php

include_once '../../includes/easyparliament/init.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, STRIPE_ENDPOINT_SECRET);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    exit();
} catch (\Stripe\Error\SignatureVerification $e) {
    http_response_code(400);
    exit();
}

$obj = $event->data->object;
if ($event->type == 'customer.subscription.deleted') {
    $db = new ParlDB;
    $sub = new \MySociety\TheyWorkForYou\Subscription($obj->id);
    if ($sub->stripe) {
        $sub->delete_from_redis();
        $db->query('DELETE FROM api_subscription WHERE stripe_id = :stripe_id', [':stripe_id' => $obj->id]);
    }
} elseif ($event->type == 'customer.subscription.updated') {
    $sub = new \MySociety\TheyWorkForYou\Subscription($obj->id);
    if ($sub->stripe) {
        # See if we're changing plan...
        $old_plan = null;
        if (property_exists($event->data, 'previous_attributes')) {
            $previous = $event->data->previous_attributes;
            if (array_key_exists('plan', $previous)) {
                $old_plan = $previous['plan']['id'];
            }
        }
        $new_plan = $obj->plan->id;
        # If we are, and it's an upgrade...
        if ($old_plan && !$sub->plan_is_same_or_lower($old_plan, $new_plan)) {
            $max_this_month = $obj->metadata['maximum_plan'];
            # And either it's a normal upgrade, or one past the previous maximum
            if (!$max_this_month || !$this->plan_is_same_or_lower($max_this_month, $new_plan)) {
                $sub->redis_update_max($new_plan);
            }
        }
    }
} elseif ($event->type == 'invoice.payment_failed' && stripe_twfy_sub($obj)) {
    $customer = \Stripe\Customer::retrieve($obj->customer);
    $email = $customer->email;
    if ($obj->next_payment_attempt) {
        send_template_email(array('template' => 'api_payment_failed', 'to' => $email), array());
    } else {
        send_template_email(array('template' => 'api_cancelled', 'to' => $email), array());
    }
} elseif ($event->type == 'invoice.payment_succeeded' && stripe_twfy_sub($obj)) {
    stripe_reset_quota($obj->subscription);
    try {
        # Update the invoice's charge to say it came from TWFY (for CSV export)
        $charge = \Stripe\Charge::retrieve($obj->charge);
        $charge->description = 'TheyWorkForYou';
        $charge->save();
    } catch (\Stripe\Error\Base $e) {
    }
} elseif ($event->type == 'invoice.updated' && stripe_twfy_sub($obj)) {
    if ($obj->forgiven && property_exists($event->data, 'previous_attributes')) {
        $previous = $event->data->previous_attributes;
        if (array_key_exists('forgiven', $previous) && !$previous['forgiven']) {
            stripe_reset_quota($obj->subscription);
        }
    }
}

http_response_code(200);

# ---

function stripe_twfy_sub($invoice) {
    # If the invoice doesn't have a subscription, ignore it
    if (!$invoice->subscription) {
        return false;
    }
    $stripe_sub = \Stripe\Subscription::retrieve($invoice->subscription);
    return substr($stripe_sub->plan->id, 0, 4) == 'twfy';
}

function stripe_reset_quota($subscription) {
    $sub = new \MySociety\TheyWorkForYou\Subscription($subscription);
    if ($sub->stripe) {
        $sub->redis_reset_quota();
        # If we'd previously been remembering a higher plan,
        # forget it now and set plan limit
        if ($sub->stripe->metadata['maximum_plan']) {
            $sub->redis_update_max($sub->stripe->plan->id);
            $sub->stripe->metadata['maximum_plan'] = '';
            $sub->stripe->save();
        }
    } else {
        $subject = "Someone's subscription was not renewed properly";
        $message = "TheyWorkForYou tried to reset the quota for subscription $subscription but couldn't find it";
        send_email(CONTACTEMAIL, $subject, $message);
    }
}
