<?php

namespace MySociety\TheyWorkForYou;

function add_vat($pence) {
    return round($pence * 1.2 / 100, 2);
}

class Subscription {
    public $stripe;
    public $upcoming;
    public $has_payment_data = false;

    private static $plans = ['twfy-1k', 'twfy-5k', 'twfy-10k', 'twfy-0k'];

    public function __construct($arg) {
        # User ID
        if (is_int($arg)) {
            $user = new \USER;
            $user->init($arg);
            $arg = $user;
        }

        $this->db = new \ParlDB;
        $this->redis = new Redis;
        if (defined('TESTING')) {
            $this->api = new TestStripe("");
        } else {
            $this->api = new Stripe(STRIPE_SECRET_KEY);
        }

        if (is_a($arg, 'User')) {
            # User object
            $this->user = $arg;
            $this->redis_prefix = "user:{$this->user->user_id}:quota:" . REDIS_API_NAME;
            $q = $this->db->query('SELECT * FROM api_subscription WHERE user_id = :user_id', [
                ':user_id' => $this->user->user_id()]);
            if ($q->rows > 0) {
                $id = $q->field(0, 'stripe_id');
            } else {
                return;
            }
        } else {
            # Assume Stripe ID string
            $id = $arg;
            $q = $this->db->query('SELECT * FROM api_subscription WHERE stripe_id = :stripe_id', [
                ':stripe_id' => $id]);
            if ($q->rows > 0) {
                $user = new \USER;
                $user->init($q->field(0, 'user_id'));
                $this->user = $user;
                $this->redis_prefix = "user:{$this->user->user_id}:quota:" . REDIS_API_NAME;
            } else {
                return;
            }
        }

        try {
            $this->stripe = $this->api->getSubscription([
                'id' => $id,
                'expand' => ['customer.default_source'],
            ]);
        } catch (\Stripe\Error\InvalidRequest $e) {
            $this->db->query('DELETE FROM api_subscription WHERE stripe_id = :stripe_id', [':stripe_id' => $id]);
            $this->delete_from_redis();
            return;
        }

        $this->has_payment_data = $this->stripe->customer->default_source;

        $data = $this->stripe;
        if ($data->discount && $data->discount->coupon && $data->discount->coupon->percent_off) {
            $this->actual_paid = add_vat(floor(
                $data->plan->amount * (100 - $data->discount->coupon->percent_off) / 100));
            $data->plan->amount = add_vat($data->plan->amount);
        } else {
            $data->plan->amount = add_vat($data->plan->amount);
            $this->actual_paid = $data->plan->amount;
        }

        try {
            $this->upcoming = $this->api->getUpcomingInvoice(["customer" => $this->stripe->customer->id]);
        } catch (\Stripe\Error\Base $e) {
        }
    }

    private function update_subscription($form_data) {
        if ($form_data['stripeToken']) {
            $this->stripe->customer->source = $form_data['stripeToken'];
            $this->stripe->customer->save();
        }

        if ($form_data['coupon']) {
            $this->stripe->coupon = $form_data['coupon'];
        } elseif ($this->stripe->discount) {
            $this->stripe->deleteDiscount();
        }

        # Stripe cannot handle "please change this person to this plan at their
        # next billing date". So we have to do it all ourselves, which is
        # complicated by dealing with them changing again before the end of the
        # current period, and so on.
        # If someone is *upgrading* their plan, with no other complications,
        # then we want to prorate it. If someone is *downgrading* their plan,
        # we don't want to prorate, or alter their quota, because they've
        # already paid (and perhaps already used) the higher amount for the
        # whole month.

        $prorate = null;
        $current_plan = $this->stripe->plan->id;
        $max_this_month = $this->stripe->metadata['maximum_plan'];

        if ($this->plan_is_same_or_lower($current_plan, $form_data['plan'])) {
            # Downgrading, no proration, remember where we were
            $prorate = false;
            if (!$max_this_month) {
                $form_data['metadata']['maximum_plan'] = $current_plan;
            }
        } else {
            # Upgrading, depends what we've already had this month
            if (!$max_this_month) {
                # Just a standard upgrade, want proration
                $prorate = true;
            } elseif ($this->plan_is_same_or_lower($max_this_month, $form_data['plan'])) {
                # An upgrade back to a previous plan (or lower), no proration
                $prorate = false;
            } else {
                # If we're upgrading to higher than where they started, we only
                # want to prorate from where they started, not where we
                # are (e.g. someone on £100/month, downgraded to £20, then
                # upgraded to £300, want to charge them 100-300, not 20-300).
                # So we upgrade back to the original without proration, then
                # upgrade to the new plan from there, removing the old
                # maximum_plan.
                $this->update_stripe_sub($max_this_month, false);
                $prorate = true;
                $form_data['metadata']['maximum_plan'] = '';
            }
        }
        $this->stripe->metadata = $form_data['metadata'];
        $this->update_stripe_sub($form_data['plan'], $prorate);
    }

    private function update_stripe_sub($plan, $prorate) {
        $this->stripe->plan = $plan;
        $this->stripe->prorate = $prorate;
        $this->stripe->cancel_at_period_end = false; # Needed in Stripe 2018-02-28
        $this->stripe->save();
    }

    private function add_subscription($form_data) {
        # Create new Stripe customer and subscription
        $cust_params = ['email' => $this->user->email()];
        if ($form_data['stripeToken']) {
            $cust_params['source'] = $form_data['stripeToken'];
        }
        $obj = $this->api->createCustomer($cust_params);
        $customer = $obj->id;

        if (!$form_data['stripeToken'] && !($form_data['plan'] == $this::$plans[0] && $form_data['coupon'] == 'charitable100')) {
            exit(1); # Should never reach here!
        }

        $obj = $this->api->createSubscription([
            'tax_percent' => 20,
            'customer' => $customer,
            'plan' => $form_data['plan'],
            'coupon' => $form_data['coupon'],
            'metadata' => $form_data['metadata']
        ]);
        $stripe_id = $obj->id;

        $this->db->query('INSERT INTO api_subscription (user_id, stripe_id) VALUES (:user_id, :stripe_id)', [
            ':user_id' => $this->user->user_id(),
            ':stripe_id' => $stripe_id,
        ]);
        $this->redis_update_max($form_data['plan']);
    }

    private function getFields() {
        $fields = ['plan', 'charitable_tick', 'charitable', 'charity_number', 'description', 'tandcs_tick', 'stripeToken'];
        $this->form_data = [];
        foreach ($fields as $field) {
            $this->form_data[$field] = get_http_var($field);
        }
    }

    private function checkValidPlan() {
        return ($this->form_data['plan'] && in_array($this->form_data['plan'], $this::$plans));
    }

    private function checkPaymentGivenIfNeeded() {
        return ($this->has_payment_data || $this->form_data['stripeToken'] || (
                    $this->form_data['plan'] == $this::$plans[0]
                && in_array($this->form_data['charitable'], ['c', 'i'])
                ));
    }

    public function checkForErrors() {
        $this->getFields();
        $form_data = &$this->form_data;

        $errors = [];
        if ($form_data['charitable'] && !in_array($form_data['charitable'], ['c', 'i', 'o'])) {
            $form_data['charitable'] = '';
        }

        if (!$this->checkValidPlan()) {
            $errors[] = 'Please pick a plan';
        }

        if (!$this->checkPaymentGivenIfNeeded()) {
            $errors[] = 'You need to submit payment';
        }

        if (!$this->stripe && !$form_data['tandcs_tick']) {
            $errors[] = 'Please agree to the terms and conditions';
        }

        if (!$form_data['charitable_tick']) {
            $form_data['charitable'] = '';
            $form_data['charity_number'] = '';
            $form_data['description'] = '';
            return $errors;
        }

        if ($form_data['charitable'] == 'c' && !$form_data['charity_number']) {
            $errors[] = 'Please provide your charity number';
        }
        if ($form_data['charitable'] == 'i' && !$form_data['description']) {
            $errors[] = 'Please provide details of your project';
        }

        return $errors;
    }

    public function createOrUpdateFromForm() {
        $form_data = $this->form_data;

        $form_data['coupon'] = null;
        if (in_array($form_data['charitable'], ['c', 'i'])) {
            $form_data['coupon'] = 'charitable50';
            if ($form_data['plan'] == $this::$plans[0]) {
                $form_data['coupon'] = 'charitable100';
            }
        }

        $form_data['metadata'] = [
            'charitable' => $form_data['charitable'],
            'charity_number' => $form_data['charity_number'],
            'description' => $form_data['description'],
        ];

        if ($this->stripe) {
            $this->update_subscription($form_data);
        } else {
            $this->add_subscription($form_data);
        }
    }

    private function plan_is_same_or_lower($old_plan, $new_plan) {
        $old_calls = $this->number_of_calls($old_plan);
        $new_calls = $this->number_of_calls($new_plan);
        if ($old_calls == 0) {
            # New plan must be same/lower than unlimited
            return true;
        } elseif ($new_calls == 0) {
            # New plan only same/lower if old plan is unlimited
            return $old_calls == 0;
        } else {
            return $new_calls <= $old_calls;
        }
    }

    private function number_of_calls($plan) {
        preg_match('#^twfy-(\d+)k#', $plan, $m);
        return (int) $m[1];
    }

    public function redis_update_max($plan) {
        $max = $this->number_of_calls($plan) * 1000;
        $this->redis->set("$this->redis_prefix:max", $max);
        $this->redis->del("$this->redis_prefix:blocked");
    }

    public function redis_reset_quota() {
        $count = $this->redis->getset("$this->redis_prefix:count", 0);
        if ($count !== null) {
            $this->redis->rpush("$this->redis_prefix:history", $count);
        }
        $this->redis->del("$this->redis_prefix:blocked");
    }

    public function delete_from_redis() {
        $this->redis->del("$this->redis_prefix:max");
        $this->redis->del("$this->redis_prefix:count");
        $this->redis->del("$this->redis_prefix:blocked");
    }

    public function quota_status() {
        return [
            'count' => floor($this->redis->get("$this->redis_prefix:count")),
            'blocked' => floor($this->redis->get("$this->redis_prefix:blocked")),
            'quota' => floor($this->redis->get("$this->redis_prefix:max")),
            'history' => $this->redis->lrange("$this->redis_prefix:history", 0, -1),
        ];
    }
}
