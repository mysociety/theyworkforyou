<?php

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/easyparliament/helper-donate.php';

// Run the stripe session if the stripe parameter is set
check_for_stripe_submission(
    "https://www.theyworkforyou.com/support-us/thanks",
    "https://www.theyworkforyou.com/support-us/failed"
);

use MySociety\TheyWorkForYou\Renderer\Markdown;

# define array of payment amounts and current/one-off options
# note - new annual and monthly payments need to be defined as new prices in stripe
# e.g. 'donate_monthly_10'.
$payment_amounts = [
    'monthly' => [
        '2' => '£2',
        '5' => '£5',
        '10' => '£10',
    ],
    'annually' => [
        '10' => '£10',
        '25' => '£25',
        '50' => '£50',
    ],
    'one-off' => [
        '5' => '£5',
        '10' => '£10',
        '50' => '£50',
    ],
];


$default_amounts = [
    'monthly' => '5',
    'annually' => '25',
    'one-off' => '10',
];


$default_type = 'monthly';

# use the how-often parameter if set, if not default to option at end of line (options are 'monthly', 'annually', or 'one-off')
$payment_type = get_http_var('how-often', $default_type);

// check $payment_type is a valid option
if (!array_key_exists($payment_type, $payment_amounts)) {
    $payment_type = $default_type;
}

# use the how-much parameter if set, if not default to default amount for initial payment type
$how_much = get_http_var('how-much', $default_amounts[$payment_type]);

# check this is a str of an int

if (!is_numeric($how_much)) {
    $how_much = $default_amounts[$payment_type];
}

$verbose_amount = "£" . number_format($how_much, 0);
if ($payment_type == 'monthly') {
    $verbose_amount .= " a month";
} elseif ($payment_type == 'annually') {
    $verbose_amount .= " a year";
} else {
    $verbose_amount .= " as a one-off payment";
}


# if how-much is not in the allowed values for the current payment type, set to 'other', and set $other_how_much to the value of how-much
if (!array_key_exists($how_much, $payment_amounts[$payment_type] ?? [])) {
    $how_much = 'other';
    $other_how_much = get_http_var('how-much');
} else {
    $other_how_much = '';
}


$markdown = new Markdown();
$markdown->markdown_document('support-us', true, [
    'donate_box' => Markdown::render_php('donate/_stripe_donate', [
        'payment_type' => $payment_type,
        'how_much' => $how_much,
        'verbose_amount' => $verbose_amount,
        'payment_amounts' => $payment_amounts,
        'default_amounts' => $default_amounts,
        'other_how_much' => $other_how_much,
    ]),
    'payment_type' => $payment_type,
    'how_much' => $how_much,
    'verbose_amount' => $verbose_amount,
    '_page_title' => 'Support Us - TheyWorkForYou',
    '_social_image_title' => 'Support Our Work']);
