<?php

namespace MySociety\TheyWorkForYou;

class TestStripe extends Stripe {
    public function getSubscription($id, $args = []) {
        if ($id == 'sub_123') {
            return \Stripe\Util\Util::convertToStripeObject([
                'id' => 'sub_123',
                'discount' => [
                    'coupon' => ['percent_off' => 100],
                    'end' => null,
                ],
                'schedule' => null,
                'plan' => [
                    'amount' => '2000',
                    'id' => 'twfy-1k',
                    'nickname' => 'Some calls per month',
                    'interval' => 'month',
                ],
                'cancel_at_period_end' => false,
                'created' => time(),
                'current_period_end' => time(),
                'latest_invoice' => [],
                'customer' => [
                    'id' => 'cus_123',
                    'balance' => 0,
                    'default_source' => [],
                    'invoice_settings' => [
                        'default_payment_method' => [],
                    ],
                ],
            ], null);
        } elseif ($id == 'sub_456') {
            return \Stripe\Util\Util::convertToStripeObject([
                'id' => 'sub_456',
                'discount' => null,
                'schedule' => [
                    'id' => 'sub_sched',
                    'phases' => [
                    ],
                ],
                'plan' => [
                    'amount' => '4167',
                    'id' => 'twfy-5k',
                    'nickname' => 'Many calls per month',
                    'interval' => 'month',
                ],
                'cancel_at_period_end' => false,
                'created' => time(),
                'current_period_end' => time(),
                'latest_invoice' => [],
                'customer' => [
                    'id' => 'cus_456',
                    'balance' => 0,
                    'default_source' => [],
                    'invoice_settings' => [
                        'default_payment_method' => [],
                    ],
                ],
            ], null);
        } elseif ($id == 'sub_upgrade') {
            return \Stripe\Util\Util::convertToStripeObject([
                'id' => 'sub_upgrade',
                'discount' => null,
                'schedule' => null,
                'plan' => [
                    'amount' => '4167',
                    'id' => 'twfy-5k',
                    'nickname' => 'Many calls per month',
                    'interval' => 'month',
                ],
                'cancel_at_period_end' => false,
                'created' => time(),
                'current_period_end' => time(),
                'latest_invoice' => [],
                'customer' => [
                    'id' => 'cus_123',
                    'balance' => 0,
                    'default_source' => [],
                    'invoice_settings' => [
                        'default_payment_method' => [],
                    ],
                ],
            ], null);
        }
        return \Stripe\Util\Util::convertToStripeObject([], null);
    }

    public function getUpcomingInvoice($args) {
        return \Stripe\Util\Util::convertToStripeObject([], null);
    }

    public function createCustomer($args) {
        return \Stripe\Util\Util::convertToStripeObject([
            'id' => 'cus_123',
            'email' => 'test@example.org',
        ], null);
    }

    public function updateCustomer($id, $args) {
        return \Stripe\Util\Util::convertToStripeObject([], null);
    }

    public function createSubscription($args) {
        if ($args['metadata']['charity_number'] == 'up-test') {
            $id = 'sub_upgrade';
        } elseif ($args['plan'] == 'twfy-5k') {
            $id = 'sub_456';
        } else {
            $id = 'sub_123';
        }
        return \Stripe\Util\Util::convertToStripeObject([
            'id' => $id,
        ], null);
    }

    public function createSchedule($id) {
        return \Stripe\Util\Util::convertToStripeObject([
            'id' => 'schedule_1',
            'phases' => [
                [
                    'start_date' => time(),
                    'end_date' => time(),
                    'discounts' => null,
                    'items' => [
                        [
                            'price' => '5000',
                        ],
                    ],
                ],
            ],
        ], null);
    }

    public function updateSchedule($id, $phases) {}

    public function releaseSchedule($id) {}

    public function updateSubscription($id, $args) {}
}
