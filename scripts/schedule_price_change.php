<?php

include_once '../../includes/easyparliament/init.php';

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
\Stripe\Stripe::setApiVersion(STRIPE_API_VERSION);

$old_price = '';
$new_price = '';
$commit = false;

$q = $this->db->query('SELECT * FROM api_subscription');
foreach ($q as $sub_obj) {
    $subscription = \Stripe\Subscription::retrieve($sub_obj['stripe_id'], expand=[
        'schedule.phases.items.price']);

    # Possibilities:
    # * No schedule, just a monthly plan
    # * 3 phases: This script has already been run
    # * 2 phases: Just asked for downgrade, so at current price, then new price, then no schedule
    # * 2 phases: Already on 'new price' schedule, awaiting change - nothing to do
    # * 1 phase: Been downgraded, so new price, then no schedule
    # * 1 phase: On 'new price' schedule, change already happened - nothing to do
    if ($schedule = $subscription->schedule) {
        if (count($schedule->phases) > 2) {
            print "{subscription.id} has {len(schedule.phases)} phases, assume processed already";
            continue;
        } elseif (count($schedule->phases) == 2) {
            if ($schedule->phases[1]->items[0]->price != $old_price) {
                continue;
            }
            $phases = [
                $schedule->phases[0],
                $schedule->phases[1],
                [
                    'items': [['price': $new_price]],
                    'iterations': 1,
                    'proration_behavior': 'none',
                    'default_tax_rates': [STRIPE_TAX_RATE],
                ],
            ];
            # Maintain current discount, if any
            if ($schedule->phases[1]->discounts && $schedule->phases[1]->discounts[0]->coupon) {
                $phases[2]->discounts = [['coupon': $schedule->phases[1]->discounts[0]->coupon]];
            }
            print "{subscription.id} has two phases, adding third phase to new price";
            if ($commit) {
                \Stripe\SubscriptionSchedule::update($schedule->id, ['phases' => $phases]);
            }
        } else {  # Must be 1
            if ($schedule->phases[0]->items[0]->price != $old_price) {
                continue;
            }
            print "{subscription.id} has one phase, releasing and adding schedule to new price";
            if ($commit) {
                \Stripe\SubscriptionSchedule::release($schedule);
                new_schedule($subscription, $new_price);
            }
        }
    } else {
        if ($subscription->items->data[0]->price->id != $old_price) {
            continue;
        }
        print "{subscription.id} has no phase, adding schedule to new price";
        if ($commit) {
            new_schedule($subscription, $new_price);
        }
    }
}


function new_schedule($subscription, $new_price) {
    $schedule = \Stripe\SubscriptionSchedule::create(['from_subscription' => $subscription->id]);
    $phases = [
        [
            'items' => [['price' => $schedule->phases[0]->items[0]->price]],
            'start_date' => $schedule->phases[0]->start_date,
            'iterations': 2,
            'proration_behavior' => 'none',
            'default_tax_rates' => [STRIPE_TAX_RATE],
        ],
        [
            'items' => [['price' => $new_price]],
            'iterations' => 1,
            'proration_behavior' => 'none',
            'default_tax_rates' => [STRIPE_TAX_RATE],
        ],
    ];

    # Maintain current discount, if any
    if ($schedule->phases[0]->discounts && $schedule->phases[0]->discounts[0]->coupon) {
        $phases[0]['discounts'] = [['coupon' => $schedule->phases[0]->discounts[0]->coupon]];
        $phases[1]['discounts'] = [['coupon' => $schedule->phases[0]->discounts[0]->coupon]];
    }

    \Stripe\SubscriptionSchedule::update($schedule->id, ['phases' => $phases]);
}
