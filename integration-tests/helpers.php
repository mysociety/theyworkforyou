<?php

// $context should be a WebDriver instance, or a WebElement instance.
// $selector should be a CSS selector, as a string.
// Returns an array of element instances, or empty array if none found.
function getElementsByCss($context, $selector) {
    return $context->findElements(WebDriverBy::cssSelector($selector));
}

// $context should be a WebDriver instance, or a WebElement instance.
// $selector should be a CSS selector, as a string.
// Returns an element instance, or raises a NoSuchElementException if
// no matching elements could be found.
function getFirstElementByCss($context, $selector) {
    return $context->findElement(WebDriverBy::cssSelector($selector));
}

// Use this at the start of a test to make sure you're on the right URL.
// If you're already on the right URL, it does nothing. Saves time
// reloading pages.
function ensureUrl($webDriver, $url) {
    if($webDriver->getCurrentURL() != $url) {
        $webDriver->get($url);
    }
}

?>
