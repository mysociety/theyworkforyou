<?php

namespace MySociety\TheyWorkForYou;

class Stripe {
    private static $instance;

    public function __construct($stripeSecretKey) {
        if (self::$instance) {
            throw new \RuntimeException('Stripe could not be instantiate more than once. Check PHP implementation : https://github.com/stripe/stripe-php');
        }
        self::$instance = $this;

        \Stripe\Stripe::setApiKey($stripeSecretKey);
        \Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);
    }

    public function getSubscription($args) {
        return \Stripe\Subscription::retrieve($args);
    }

    public function getUpcomingInvoice($args) {
        return \Stripe\Invoice::upcoming($args);
    }

    public function createCustomer($args) {
        return \Stripe\Customer::create($args);
    }

    public function updateCustomer($id, $args) {
        return \Stripe\Customer::update($id, $args);
    }

    public function createSubscription($args) {
        return \Stripe\Subscription::create($args);
    }

    public function getInvoices($args) {
        return \Stripe\Invoice::all($args);
    }

    public function createSchedule($id) {
        return \Stripe\SubscriptionSchedule::create(['from_subscription' => $id]);
    }

    public function updateSchedule($id, $phases) {
        return \Stripe\SubscriptionSchedule::update($id, ['phases' => $phases]);
    }

    public function releaseSchedule($id) {
        \Stripe\SubscriptionSchedule::release($id);
    }

    public function updateSubscription($id, $args) {
        \Stripe\Subscription::update($id, $args);
    }
}
