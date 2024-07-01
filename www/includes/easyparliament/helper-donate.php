<?php
// This script contains the internal API that routes between the submission form
// and stripe.

function donate_post_api($token, $url, $data, $type, $headers = [])
{
    $headers = array_merge($headers, ["Authorization: Bearer $token"]);
    if ($type == "json") {
        $data = json_encode($data);
        $headers[] = "Accept: application/json";
        $headers[] = "Content-Type: application/json";
    } elseif ($type == "html") {
        $headers[] = "Content-Type: application/x-www-form-urlencoded";
        $data = http_build_query($data);
    }
    $options = [
        "http" => [
            "header" => $headers,
            "method" => "POST",
            "content" => $data,
            "ignore_errors" => true,
        ],
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $result = json_decode($result);
    return $result;
}

function donate_amount()
{
    $amount = $_POST["how-much"];
    if ($amount == "other") {
        $amount = $_POST["how-much-other"];
    }
    return $amount;
}

function giftaid_decision()
{
    if (isset($_POST["gift-aid"])) {
        return "Yes";
    } else {
        return "No";
    }
}

# Stripe

function stripe_post($path, $data)
{
    $result = donate_post_api(
        STRIPE_DONATE_SECRET_KEY,
        "https://api.stripe.com/v1" . $path,
        $data,
        "html"
    );
    if (isset($result->error)) {
        return "Sorry, there was an error processing your donation: {$result->error->message}. Please try again.";
    }
    return $result;
}

function stripe_session_ajax($successPage, $cancelPage)
{
    $amount = donate_amount();
    $giftaid = giftaid_decision();
    $howoften = $_POST["how-often"];
    $utm_source = $_POST["utm_source"];
    $utm_content = $_POST["utm_content"];
    $utm_medium = $_POST["utm_medium"];
    $utm_campaign = $_POST["utm_campaign"];
    $full_name = $_POST["full_name"];
    $contact_permission = $_POST["contact_permission"] ?? "No";

    # backward compatibility
    if ($howoften == "recurring") {
        $howoften = "monthly";
    }
    $validPeriods = ["monthly" => "month", "annually" => "year"];
    $period = $validPeriods[$howoften] ?? "once";

    $metadata = [
        "gift-aid" => $giftaid,
        "gift-aid-name" => $full_name,
        "utm_source" => $utm_source,
        "utm_content" => $utm_content,
        "utm_medium" => $utm_medium,
        "utm_campaign" => $utm_campaign,
        "contact_permission" => $contact_permission,
    ];
    // set billing addres var to required if giftaid is true
    if ($giftaid == "Yes") {
        $collectBilling = "required";
        $name = "Donation to mySociety (with gift aid)";
    } else {
        $collectBilling = "auto";
        $name = "Donation to mySociety";
    }
    $data = [
        "payment_method_types" => ["card", "bacs_debit", "paypal"],
        "success_url" => $successPage,
        "cancel_url" => $cancelPage,
        "billing_address_collection" => $collectBilling,
    ];

    $mysocDesc = "
    mySociety is a charity committed to making a fairer society 
    by providing digital services, research and data, openly and 
    at no cost. We use technology to help people understand and 
    take part in the decisions that affect their lives and communities.
    We run services such as TheyWorkForYou, WhatDoTheyKnow and FixMyStreet.
    ";

    if ($period == "once") {
        // one off payments
        $data += [
            "mode" => "payment",
            "payment_intent_data" => [
                "metadata" => $metadata,
            ],
            "submit_type" => "donate",
            "line_items" => [
                [
                    "amount" => $amount * 100,
                    "currency" => "gbp",
                    "name" => $name,
                    "description" => $mysocDesc,
                    "quantity" => 1,
                ],
            ],
        ];
    } else {
        // recurring subscription payments
        $data += [
            "mode" => "subscription",
            "subscription_data" => [
                "metadata" => $metadata,
            ],
            "line_items" => [
                [
                    "price_data" => [
                        "unit_amount" => $amount * 100,
                        "currency" => "gbp",
                        "product_data" => [
                            "name" => $name,
                            "description" => $mysocDesc,
                        ],
                        "recurring" => ["interval" => $period],
                    ],
                    "quantity" => 1,
                ],
            ],
        ];
    }
    $result = stripe_post("/checkout/sessions", $data);
    if (is_string($result)) {
        return ["error" => $result];
    } else {
        return $result;
    }
}

function verify_recaptcha()
{
    $url = "https://www.google.com/recaptcha/api/siteverify";
    $data = [
        "secret" => OPTION_RECAPTCHA_SECRET,
        "response" => $_POST["g-recaptcha-response"],
    ];
    $headers = ["Content-Type: application/x-www-form-urlencoded"];

    $options = [
        "http" => [
            "header" => $headers,
            "method" => "POST",
            "content" => http_build_query($data),
            "ignore_errors" => true,
        ],
    ];
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $result = json_decode($result);
    if ($result->success) {
        return;
    }
    return join(", ", $result->{'error-codes'});
}

function check_for_stripe_submission(
    $successPage = "https://www.mysociety.org/donate/donation-thanks/",
    $cancelPage = "https://www.mysociety.org/donate/donation-cancelled/"
) {
    // If a get request with a stripe parameter
    // Run the script session and return either
    // the success json or an error
    if (get_http_var("stripe")) {
        $error = verify_recaptcha();
        if ($error) {
            $result = ["error" => $error];
        } else {
            $result = stripe_session_ajax($successPage, $cancelPage);
        }
        header("Content-Type: application/json");
        print json_encode($result);
        exit();
    }
}

function get_checked($value, $checked_value, $echo = true)
{
    $checked = $value == $checked_value ? 'checked="checked"' : "";

    if ($echo) {
        echo $checked;
    } else {
        return $checked;
    }
}

function wp_esc_attr($text)
{
    return htmlspecialchars($text, ENT_QUOTES, "UTF-8");
}
