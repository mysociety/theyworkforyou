<?php

$appeal_start = strtotime('2026-07-01 00:00:00');
$appeal_end = strtotime('2026-07-16 23:59:59');

$now = time();
$voting_appeal_active = ($now >= $appeal_start && $now <= $appeal_end);

// Admin override for testing (e.g. ?show_voting_appeal=1 / ?show_voting_appeal=0)
if (isset($_GET['show_voting_appeal'])) {
    $voting_appeal_active = $_GET['show_voting_appeal'] === '1';
}

// Expanded by default; remember if the user has collapsed it (own cookie, lasts 1 week).
// Uses a distinct cookie name so collapsing the appeal does not affect the standard
// donation banner shown on other MP pages, and vice-versa.
$appeal_expanded = !isset($_COOKIE['voting_appeal_collapsed']);

$current_page = isset($pagetype) && $pagetype ? $pagetype : 'votes';
$utm = 'utm_source=' . urlencode($current_page) . '&utm_campaign=twfy_voting_appeal';
?>

<?php if ($voting_appeal_active) { ?>
    <div class="panel panel--donation-banner panel--donation-banner--appeal" id="voting-appeal">
        <div class="donation-banner__content">
            <div class="donation-banner__header">
                <h3 class="donation-banner__title">
                    <span class="donation-banner__icon">💚</span>
                    We've updated our voting summaries
                </h3>
                <button class="donation-banner__toggle js-voting-appeal-toggle" aria-expanded="<?= $appeal_expanded ? 'true' : 'false' ?>" aria-controls="voting-appeal-details">
                    <span class="donation-banner__toggle-text">Why we need your support</span>
                    <span class="donation-banner__toggle-arrow">↓</span>
                </button>
            </div>

            <div id="voting-appeal-details" class="donation-banner__details<?= $appeal_expanded ? ' is-open' : '' ?>">
                <div class="donation-banner__message">
                    <p>
                        Our small team <a href="https://www.mysociety.org/2026/07/01/theyworkforyou-voting-summaries-update-july-2026/">works hard</a> to show how your MP has voted on the issues that matter to you.
                    </p>
                    <p>
                        Your donations help keep TheyWorkForYou running and independent. If it's useful to you, please help keep it going.
                    </p>
                </div>

                <div class="donation-banner__actions">
                    <a href="/support-us/?<?= $utm ?>#donate-form" class="button tertiary">
                        Support TheyWorkForYou
                    </a>
                    <a href="/support-us/?how-often=monthly&how-much=5&<?= $utm ?>#donate-form" class="button button--outline">
                        £5/month
                    </a>
                    <a href="/support-us/?how-often=one-off&how-much=10&<?= $utm ?>#donate-form" class="button button--outline">
                        £10 one-off
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Toggle functionality for the voting appeal banner
    (function() {
        const toggle = document.querySelector('.js-voting-appeal-toggle');
        const details = document.querySelector('#voting-appeal-details');

        if (toggle && details) {
            toggle.addEventListener('click', function() {
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

                if (isExpanded) {
                    details.classList.remove('is-open');
                    toggle.setAttribute('aria-expanded', 'false');

                    // Set cookie to remember collapsed state for 1 week
                    var expires = new Date();
                    expires.setTime(expires.getTime() + (7 * 24 * 60 * 60 * 1000)); // 7 days
                    document.cookie = 'voting_appeal_collapsed=1;expires=' + expires.toUTCString() + ';path=/';
                } else {
                    details.classList.add('is-open');
                    toggle.setAttribute('aria-expanded', 'true');

                    // Remove the collapsed cookie
                    document.cookie = 'voting_appeal_collapsed=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
                }
            });
        }
    })();
    </script>
<?php } else { ?>
    <?php
    // Outside the campaign window, fall back to the standard 20% donation banner.
    include __DIR__ . '/_donation_banner.php';
    ?>
<?php }; ?>