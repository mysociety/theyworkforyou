<?php

namespace MySociety\TheyWorkForYou;

class Stripe {
    private static $instance;

    public function __construct($stripeSecretKey = "") {
        if (self::$instance) {
            throw new \RuntimeException('Stripe could not be instantiate more than once. Check PHP implementation : https://github.com/stripe/stripe-php');
        }
        self::$instance = $this;

        # Not present in testing
        if ($stripeSecretKey) {
            $this->client = new \Stripe\StripeClient([
                "api_key" => $stripeSecretKey,
                "stripe_version" => STRIPE_API_VERSION,
            ]);
        }
    }

    public function getSubscription($id, $args = []) {
        return $this->client->subscriptions->retrieve($id, $args);
    }

    public function getUpcomingInvoice($args) {
        return $this->client->invoices->upcoming($args);
    }

    public function createCustomer($args) {
        return $this->client->customers->create($args);
    }

    public function updateCustomer($id, $args) {
        return $this->client->customers->update($id, $args);
    }

    public function createSubscription($args) {
        return $this->client->subscriptions->create($args);
    }

    public function getInvoices($args) {
        return $this->client->invoices->all($args);
    }

    public function createSchedule($id) {
        return $this->client->subscriptionSchedules->create(['from_subscription' => $id]);
    }

    public function updateSchedule($id, $phases) {
        return $this->client->subscriptionSchedules->update($id, ['phases' => $phases]);
    }

    public function releaseSchedule($id) {
        $this->client->subscriptionSchedules->release($id);
    }

    public function updateSubscription($id, $args) {
        $this->client->subscriptions->update($id, $args);
    }
}
