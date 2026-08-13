#!/usr/bin/php

<?php

// Ensures API keys and subscription quotas are configured correctly
// in Redis.
// Will also delete subscriptions in DB if stripe says they are cancelled.
// Pass '--commit' to run for real.

include_once dirname(__FILE__) . '/../www/includes/easyparliament/init.php';
use Predis\Collection\Iterator\Keyspace;

$commit = ($argv[1] ?? '' == '--commit');
if ($commit) {
    echo "Running for real.\n\n";
} else {
    echo "Dry run. Pass --commit to run for real.\n\n";
}

$db = new ParlDB();
$redis = new \MySociety\TheyWorkForYou\Redis();
$stripe = new \MySociety\TheyWorkForYou\Stripe(STRIPE_SECRET_KEY);

echo "---------------------\n";
echo " Reconciling API Keys\n";
echo "---------------------\n";

echo "\nFinding all API keys in Redis.\n";

$api_keys_in_redis = [];
foreach (new Keyspace($redis, 'key:*:api:' . REDIS_API_NAME, 100) as $redis_key) {
    if (!preg_match('/key:([^:]+):api:' . REDIS_API_NAME . '/', $redis_key, $m)) {
        echo "    Couldn't parse key '$redis_key'. Skipping.\n";
        continue;
    }

    $api_keys_in_redis[$m[1]] = $redis_key;
}

$api_keys_in_redis_count = count($api_keys_in_redis);
echo "Found $api_keys_in_redis_count API keys in redis.\n";

echo "\nGetting all API keys in DB.\n";

$q = $db->query("SELECT api_key, user_id FROM api_key");  # Including disabled ones.

$db_api_keys = [];
foreach ($q as $api_key) {
    $key = $api_key['api_key'];
    $user_id = $api_key['user_id'];
    if (!($key && $user_id)) {
        continue;
    }
    $db_api_keys[$key] = $user_id;
}
$db_api_keys_count = count($db_api_keys);
echo "Got $db_api_keys_count API keys in DB.\n";

echo "\nSeeing which API keys in Redis need deleting.\n";
$redis_api_keys_to_delete = [];
foreach ($api_keys_in_redis as $api_key => $redis_key) {
    if (!array_key_exists($api_key, $db_api_keys)) {
        echo "    No match in DB for $api_key. Will delete.\n";
        array_push($redis_api_keys_to_delete, $redis_key);
    }
}
$redis_api_keys_to_delete_count = count($redis_api_keys_to_delete);
if ($redis_api_keys_to_delete_count > 0) {
    echo "Found $redis_api_keys_to_delete_count keys to delete.\n";
    if ($commit) {
        $redis->del($redis_api_keys_to_delete);
        echo "Keys deleted.\n";
    } else {
        echo "Running in dry mode so skipping.\n";
    }
} else {
    echo "Found no keys to delete.\n";
}

echo "\nEnsuring keys are set in Redis for API keys in DB.\n";

# Remap keys to redis keys.
$db_api_keys_by_redis_key = array_combine(
    array_map(function ($k) {
        return "key:$k:" . REDIS_API_NAME;
    }, array_keys($db_api_keys)),
    $db_api_keys
);

if ($commit) {
    $redis->mset($db_api_keys_by_redis_key);
    echo "Keys set.\n\n";
} else {
    echo "Running in dry mode so skipping.\n\n";
}

echo "--------------------------------\n";
echo " Reconciling Subscription Quotas\n";
echo "--------------------------------\n";

echo "\nFinding all quotas in Redis.\n";

$quotas_in_redis = [];
foreach (new Keyspace($redis, 'user:*:quota:' . REDIS_API_NAME . ":max", 100) as $redis_key) {
    if (!preg_match('/user:([^:]+):quota:' . REDIS_API_NAME . ':max/', $redis_key, $m)) {
        echo "    Couldn't parse key '$redis_key'. Skipping.\n";
        continue;
    }
    array_push($quotas_in_redis, $m[1]);
}

$quotas_in_redis_count = count($quotas_in_redis);
echo "Found $quotas_in_redis_count quotas in Redis.\n";

echo "\nLooking at all subs in DB and associated stripe info.\n";

$q = $db->query("SELECT user_id, stripe_id FROM api_subscription");

$all_sub_user_ids = [];
foreach ($q as $sub_row) {
    $user_id = $sub_row['user_id'];
    array_push($all_sub_user_ids, $user_id);

    $stripe_id = $sub_row['stripe_id'];
    if (!($stripe_id && $user_id)) {
        continue;
    }

    # Pass stripe to avoid bumping into the 'one instance per process' limit.
    # Pass false for 'delete_on_stripe_invalid' - arguably something we could do
    # here on non-dry runs but being cautious at least initially.
    $sub = new MySociety\TheyWorkForYou\Subscription($stripe_id, $stripe, false);
    $status = $sub->stripe['status'] ?? false;

    echo "\n";
    if (!$status) {
        echo "    Sub constructor did not return a stripe status for ID '$stripe_id'.\n";
        echo "    This could be transient Stripe API failure, a delete race,\n";
        echo "    or Stripe returning 'invalid request'.\n";
        echo "    In any case, no further operation will be made for this sub.\n";
        continue;
    }

    echo "    Stripe sub '$stripe_id' status is '$status'.\n";

    if ($status == 'active' || $status == 'past_due') {
        $plan = $sub->stripe['items']['data'][0]['plan']['id'] ?? false;
        if (!$plan) {
            echo "    Unable to read plan for sub; skipping.\n";
            continue;
        }
        echo "    Ensuring quota max is set ($plan).\n";
        if (!$commit) {
            echo "    Skipping; dry mode.\n";
            continue;
        }
        $sub->redis_update_max($plan, false);  # Update max but don't unblock.
        echo "    Quota max set.\n";
    } elseif ($status == 'canceled') {
        echo "    Deleting quota in Redis and also the sub from DB.\n";
        if (!$commit) {
            echo "    Skipping; dry mode.\n";
            continue;
        }
        $db->query(
            'DELETE FROM api_subscription WHERE stripe_id = :stripe_id',
            [':stripe_id' => $stripe_id]
        );
        $sub->delete_from_redis();
        echo "    Sub and quota deleted.\n";
    } else {
        echo "    Status is unexpected; taking no action.\n";
    }
}

$subs_count = count($all_sub_user_ids);
echo "\nFinished looking at $subs_count subs in DB.\n";

echo "\nSeeing which quotas in Redis need deleting.\n";
$deleted = 0;
$quotas_to_delete = [];
foreach ($quotas_in_redis as $quota_user_id) {
    if (!in_array($quota_user_id, $all_sub_user_ids)) {
        echo "    No sub in DB for $quota_user_id. Deleting.\n";
        $deleted++;
        if (!$commit) {
            echo "    Skipping; dry mode.\n";
            continue;
        }
        $redis_prefix = "user:$quota_user_id:quota:" . REDIS_API_NAME;
        $redis->del([
            "$redis_prefix:max",
            "$redis_prefix:count",
            "$redis_prefix:blocked",
        ]);
        echo "    Quota deleted.\n";
    }
}

if ($commit) {
    echo "Deleted $deleted quotas in Redis.\n";
} else {
    echo "Would delete $deleted quotas in Redis.\n";
}
