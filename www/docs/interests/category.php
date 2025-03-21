<?php

// Logic page for the register of interests by category view
// if no selected category, will just show a list of the categories

include_once '../../includes/easyparliament/init.php';

$chamber = get_http_var("chamber", "house-of-commons");
$date = get_http_var("date", null);
$selected_category_id = get_http_var("category_id", null);

// ensure $date if set is a valid iso date
if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = null;
}

if ($chamber == 'house-of-commons') {
    $this_page = "interest_category";
} elseif ($chamber == 'scottish-parliament') {
    $this_page = "interest_category_sp";
} elseif ($chamber == 'senedd') {
    $this_page = "interest_category_wp";
} elseif ($chamber == 'northern-ireland-assembly') {
    $this_page = "interest_category_ni";
} else {
    $this_page = "interest_category";
}

// load a register
if ($date) {
    $register = MySociety\TheyWorkForYou\DataClass\Regmem\Register::latestAsOfDate($chamber, $date);
} else {
    $register = MySociety\TheyWorkForYou\DataClass\Regmem\Register::getLatest($chamber);
}


// get the relevant categories from that register
// and if there is a selected_category_id reduce the register here
$categories = [];

foreach ($register->persons as $person) {
    foreach ($person->categories as $category) {
        if (!isset($categories[$category->category_id])) {
            $categories[$category->category_id] = $category->category_name;
        }
    }
    if ($selected_category_id) {
        $person->categories->limitToCategoryIds([$selected_category_id]);
    }
}

// sort $categories by key
ksort($categories);

// if house-of-commons, drop category '1'
// this is just holding details that are more useful in 1.1 and 1.2
if ($chamber == 'house-of-commons' && isset($categories["1"])) {
    unset($categories["1"]);
}

// populate a category_emoji lookup
$category_emojis = [];
foreach ($categories as $category_id => $category_name) {
    $category_emojis[$category_id] = MySociety\TheyWorkForYou\DataClass\Regmem\Category::emojiLookup($category_name);
}

$context = [
    "register" => $register,
    "categories" => $categories,
    "selected_category_id" => $selected_category_id,
    "selected_category_name" => $selected_category_id ? $categories[$selected_category_id] : null,
    "chamber_slug" => $chamber,
    "category_emojis" => $category_emojis,
];

MySociety\TheyWorkForYou\Renderer::output('interests/category', $context);
