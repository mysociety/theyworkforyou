<?php

namespace MySociety\TheyWorkForYou;

function add_vat($pence) {
    return round($pence * 1.2 / 100, 2);
}

class Subscription {
    public $stripe;
    public $upcoming;
    public $has_payment_data = false;

    private static $prices = ['twfy-1k', 'twfy-5k', 'twfy-10k', 'twfy-0k'];
    private static $amounts = [2000, 5000, 10000, 30000];

    public function __construct($arg) {
        # User ID
        if (is_int($arg)) {
            $user = new \USER();
            $user->init($arg);
            $arg = $user;
        }

        $this->db = new \ParlDB();
        $this->redis = new Redis();
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
                ':user_id' => $this->user->user_id()])->first();
            if ($q) {
                $id = $q['stripe_id'];
            } else {
                return;
            }
        } else {
            # Assume Stripe ID string
            $id = $arg;
            $q = $this->db->query('SELECT * FROM api_subscription WHERE stripe_id = :stripe_id', [
                ':stripe_id' => $id])->first();
            if ($q) {
                $user = new \USER();
                $user->init($q['user_id']);
                $this->user = $user;
                $this->redis_prefix = "user:{$this->user->user_id}:quota:" . REDIS_API_NAME;
            } else {
                return;
            }
        }

        try {
            $this->stripe = $this->api->getSubscription([
                'id' => $id,
                'expand' => [
                    'customer.default_source',
                    'customer.invoice_settings.default_payment_method',
                    'latest_invoice.payment_intent',
                    'schedule.phases.items.price',
                ],
            ]);
            $this->stripe->price = $this->stripe->items->data[0]->price;
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            $this->db->query('DELETE FROM api_subscription WHERE stripe_id = :stripe_id', [':stripe_id' => $id]);
            $this->delete_from_redis();
            return;
        }

        $this->has_payment_data = $this->stripe->customer->default_source || $this->stripe->customer->invoice_settings->default_payment_method;
        if ($this->stripe->customer->invoice_settings->default_payment_method) {
            $this->card_info = $this->stripe->customer->invoice_settings->default_payment_method->card;
        } else {
            $this->card_info = $this->stripe->customer->default_source;
        }

        $data = $this->stripe;
        if ($data->discount && $data->discount->coupon && $data->discount->coupon->percent_off) {
            $this->actual_paid = add_vat(floor(
                $data->price->unit_amount * (100 - $data->discount->coupon->percent_off) / 100
            ));
            $data->price->unit_amount = add_vat($data->price->unit_amount);
        } else {
            $data->price->unit_amount = add_vat($data->price->unit_amount);
            $this->actual_paid = $data->price->unit_amount;
        }

        try {
            $this->upcoming = $this->api->getUpcomingInvoice(["customer" => $this->stripe->customer->id]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
        }
    }

    private function update_subscription($form_data) {
        if ($form_data['payment_method']) {
            $this->update_payment_method($form_data['payment_method']);
        }

        foreach ($this::$prices as $i => $price) {
            if ($price == $form_data['price']) {
                $new_price = $this::$amounts[$i];
                if ($form_data['coupon'] == 'charitable100') {
                    $new_price = 0;
                } elseif ($form_data['coupon'] == 'charitable50') {
                    $new_price /= 2;
                }
            }
            if ($price == $this->stripe->price->id) {
                $old_price = $this::$amounts[$i];
                if ($this->stripe->discount && ($coupon = $this->stripe->discount->coupon)) {
                    if ($coupon->percent_off == 100) {
                        $old_price = 0;
                    } elseif ($coupon->percent_off == 50) {
                        $old_price /= 2;
                    }
                }
            }
        }

       if ($old_price >= $new_price) {
            if ($this->stripe->schedule) {
                \Stripe\SubscriptionSchedule::release($this->stripe->schedule);
            }
            $schedule = \Stripe\SubscriptionSchedule::create(['from_subscription' => $this->stripe->id]);
            $phases = [
                [
                    'items' => [['price' => $schedule->phases[0]->items[0]->price]],
                    'start_date' => $schedule->phases[0]->start_date,
                    'end_date' => $schedule->phases[0]->end_date,
                    'proration_behavior' => 'none',
                    'default_tax_rates' => [STRIPE_TAX_RATE],
                ],
                [
                    'items' => [['price' => $form_data['price']]],
                    'iterations' => 1,
                    'metadata' => $form_data['metadata'],
                    'proration_behavior' => 'none',
                    'default_tax_rates' => [STRIPE_TAX_RATE],
                ],
            ];
            if ($schedule->phases[0]->discounts && $schedule->phases[0]->discounts[0]->coupon) {
                $phases[0]['discounts'] = [['coupon' => $schedule->phases[0]->discounts[0]->coupon]];
            }
            if ($form_data['coupon']) {
                $phases[1]['coupon'] = $form_data['coupon'];
            }
            \Stripe\SubscriptionSchedule::update($schedule->id, ['phases' => $phases]);
        }

        if ($old_price < $new_price) {
            $args = [
                'payment_behavior' => 'allow_incomplete',
                'items' => [['price' => $form_data['price']]],
                'metadata' => $form_data['metadata'],
                'cancel_at_period_end' => false, # Needed in Stripe 2018-02-28
                'proration_behavior' => 'always_invoice',
            ];
            if ($form_data['coupon']) {
                $args['coupon'] = $form_data['coupon'];
            } elseif ($this->stripe->discount) {
                $args['coupon'] = '';
            }
            if ($this->stripe->schedule) {
                \Stripe\SubscriptionSchedule::release($this->stripe->schedule);
            }
            \Stripe\Subscription::update($this->stripe->id, $args);
        }
    }

    private function update_customer($args) {
        $this->api->updateCustomer($this->stripe->customer->id, $args);
    }

    public function update_email($email) {
        $this->update_customer([ 'email' => $email ]);
    }

    public function update_payment_method($payment_method) {
        $payment_method = \Stripe\PaymentMethod::retrieve($payment_method);
        $payment_method->attach(['customer' => $this->stripe->customer->id]);
        $this->update_customer([
            'invoice_settings' => [
                'default_payment_method' => $payment_method,
            ],
        ]);
    }

    private function add_subscription($form_data) {
        # Create new Stripe customer and subscription
        $cust_params = ['email' => $this->user->email()];
        if ($form_data['stripeToken']) {
            $cust_params['source'] = $form_data['stripeToken'];
        }

        # At the point the customer is created, details such as postcode and
        # security code can be checked, and therefore fail
        try {
            $obj = $this->api->createCustomer($cust_params);
        } catch (\Stripe\Exception\CardException $e) {
            $body = $e->getJsonBody();
            $err  = $body['error'];
            $error = 'Sorry, we could not process your payment, please try again. ';
            $error .= 'Our payment processor returned: ' . $err['message'];
            unset($_POST['stripeToken']); # So card form is shown again
            return [ $error ];
        }

        $customer = $obj->id;

        if (!$form_data['stripeToken'] && !($form_data['price'] == $this::$prices[0] && $form_data['coupon'] == 'charitable100')) {
            exit(1); # Should never reach here!
        }

        $obj = $this->api->createSubscription([
            'payment_behavior' => 'allow_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
            'default_tax_rates' => [STRIPE_TAX_RATE],
            'customer' => $customer,
            'items' => [['price' => $form_data['price']]],
            'coupon' => $form_data['coupon'],
            'metadata' => $form_data['metadata'],
        ]);
        $stripe_id = $obj->id;

        $this->db->query('INSERT INTO api_subscription (user_id, stripe_id) VALUES (:user_id, :stripe_id)', [
            ':user_id' => $this->user->user_id(),
            ':stripe_id' => $stripe_id,
        ]);
    }

    public function invoices() {
        $invoices = $this->api->getInvoices([
            'subscription' => $this->stripe->id,
            'limit' => 24,
        ]);
        $invoices = $invoices->data;
        return $invoices;
    }

    private function getFields() {
        $fields = ['price', 'charitable_tick', 'charitable', 'charity_number', 'description', 'tandcs_tick', 'stripeToken', 'payment_method'];
        $this->form_data = [];
        foreach ($fields as $field) {
            $this->form_data[$field] = get_http_var($field);
        }
    }

    private function checkValidPrice() {
        return ($this->form_data['price'] && in_array($this->form_data['price'], $this::$prices));
    }

    private function checkPaymentGivenIfNeeded() {
        $payment_data = $this->form_data['stripeToken'] || $this->form_data['payment_method'];
        return ($this->has_payment_data || $payment_data || (
            $this->form_data['price'] == $this::$prices[0]
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

        if (!$this->checkValidPrice()) {
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
            if ($form_data['price'] == $this::$prices[0]) {
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
            return $this->add_subscription($form_data);
        }
    }

    public function redis_update_max($price) {
        preg_match('#^twfy-(\d+)k#', $price, $m);
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
