<?php

include_once '../../includes/easyparliament/init.php';

// Check if the Merriweather font is already downloaded
$fontDir = __DIR__ . '/../vendor/fonts';
$merriweatherFontPath = $fontDir . '/Merriweather.ttf';

if (!file_exists($merriweatherFontPath)) {
    // Ensure the font directory exists
    if (!is_dir($fontDir)) {
        mkdir($fontDir, 0755, true);
    }

    // Download the Merriweather font
    $fontUrl = 'https://github.com/google/fonts/raw/refs/heads/main/ofl/merriweather/Merriweather%5Bopsz,wdth,wght%5D.ttf';
    $fontData = file_get_contents($fontUrl);

    if ($fontData === false) {
        http_response_code(500);
        echo 'Failed to download Merriweather font';
        exit;
    }

    file_put_contents($merriweatherFontPath, $fontData);
}

// Note: may want different versions of this for different parliaments
$baseImagePath = __DIR__ . '/../images/og/social_twfy_blank.png';

// Check if the base image exists
if (!file_exists($baseImagePath)) {
    http_response_code(404);
    echo 'Base image not found';
    exit;
}

// check if the font file exists, throw error
if (!file_exists($merriweatherFontPath)) {
    http_response_code(404);
    echo 'Font file not found';
    exit;
}

// Get the heading and subheading parameters from the request
$heading = $_GET['heading'] ?? "";
$subheading = $_GET['subheading'] ?? "";


// We're using a hash to avoid arbitrary image generation
$hash = $_GET['hash'] ?? '';
// Truncate the hash to the first 10 characters for validation
$expectedHash = substr(hash_hmac('sha256', $heading . $subheading, OPENGRAPH_IMAGE_SALT), 0, 10);
if (!hash_equals($expectedHash, $hash)) {
    http_response_code(403);
    echo 'Invalid hash';
    exit;
}

// Wrap the title text to a new line if it exceeds 16 characters
if (strlen($heading) > 16) {
    $heading = wordwrap($heading, 16, "\n", true);
    $subheading = ""; // Clear the subheading to avoid overlap
}


// Split the subheading into multiple lines if it exceeds 40 characters
if (strlen($subheading) > 40) {
    $subheading = wordwrap($subheading, 40, "\n", true);
}

$image = new Imagick($baseImagePath);

$drawHeading = new ImagickDraw();
$drawHeading->setFont($merriweatherFontPath);
$drawHeading->setFontSize(100);
$drawHeading->setFillColor(new ImagickPixel('black'));
$drawHeading->setGravity(Imagick::GRAVITY_CENTER);

$image->annotateImage($drawHeading, 0, -30, 0, $heading);

$drawSubheading = new ImagickDraw();
$drawSubheading->setFont($merriweatherFontPath);
$drawSubheading->setFontSize(50);
$drawSubheading->setFillColor(new ImagickPixel('black'));
$drawSubheading->setGravity(Imagick::GRAVITY_CENTER);

$lines = explode("\n", $subheading);
$lineHeight = 60; // Adjust line height as needed
$yOffset = 50; // Initial Y offset for the first line

foreach ($lines as $line) {
    $image->annotateImage($drawSubheading, 0, $yOffset, 0, $line);
    $yOffset += $lineHeight; // Move down for the next line
}

// Send the image to the browser with the appropriate content type
header('Content-Type: image/png');

echo $image;

$image->clear();
