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
            $arg = new \USER($arg);
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
                $this->user = new \USER($q->field(0, 'user_id'));
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

        # Update Stripe subscription
        $this->stripe->plan = $form_data['plan'];
        if ($form_data['coupon']) {
            $this->stripe->coupon = $form_data['coupon'];
        } elseif ($this->stripe->discount) {
            $this->stripe->deleteDiscount();
        }
        $this->stripe->metadata = $form_data['metadata'];
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

        $this->redis_update_max($form_data['plan']);
    }

    public function redis_update_max($plan) {
        preg_match('#^twfy-(\d+)k#', $plan, $m);
        $max = $m[1] * 1000;
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
