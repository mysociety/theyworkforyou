<?php
// Wikipedia-style donation banner for MP profile pages
// This appears prominently at the top of MP profiles to encourage donations

// Include current page in randomization so banner appears on different pages for different users
$current_page = isset($pagetype) && $pagetype ? $pagetype : 'profile';

// Show banner to 20% of visitors - use a combination of IP, user agent, date, and current page for daily rotation
$user_identifier = $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . date('Y-m-d') . $current_page;
$hash = crc32($user_identifier);
$show_banner = ($hash % 100) < 20; // 20% chance

// Admin override for testing
if (isset($_GET['show_donation_banner'])) {
    $show_banner = $_GET['show_donation_banner'] === '1';
}
?>

<?php if ($show_banner): ?>
<div class="panel panel--donation-banner">
    <div class="donation-banner__content">
        <div class="donation-banner__header">
            <h3 class="donation-banner__title">
                <span class="donation-banner__icon">💚</span>
                Help keep TheyWorkForYou free and independent
            </h3>
            <button class="donation-banner__toggle js-donation-banner-toggle" aria-expanded="false" aria-controls="donation-banner-details">
                <span class="donation-banner__toggle-text">Why we need your support</span>
                <span class="donation-banner__toggle-arrow">↓</span>
            </button>
        </div>
        
        <div id="donation-banner-details" class="donation-banner__details">
            <div class="donation-banner__message">
                <p><strong>For over 20 years, TheyWorkForYou has been making our democracy more transparent and our politicians more accountable.</strong> We need your support to:</p>
                
                <ul class="donation-banner__benefits">
                    <li><strong>Hold Power to Account</strong> — We highlight what our representatives are doing so they know the public is watching.</li>
                    <li><strong>Keep It Free and Accessible</strong> — No paywalls because everyone deserves unbiased information about decisions made on their behalf.</li>
                    <li><strong>Innovate and Go Further</strong> — We're always looking for new ways to improve our democracy.</li>
                </ul>
                
                <p>We're not funded by the government and we run this for the public, not MPs. <strong>We can't wait for a better political system to be given to us – we want to work together to make it happen now.</strong></p>
            </div>
            
            <div class="donation-banner__actions">
                <a href="/support-us/?utm_source=<?= urlencode($current_page) ?>#donate-form" class="button tertiary">
                    Support TheyWorkForYou
                </a>
                <a href="/support-us/?how-often=monthly&how-much=5&utm_source=<?= urlencode($current_page) ?>#donate-form" class="button button--outline">
                    £5/month
                </a>
                <a href="/support-us/?how-often=one-off&how-much=10&utm_source=<?= urlencode($current_page) ?>#donate-form" class="button button--outline">
                    £10 one-off
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle functionality for the donation banner
(function() {
    const toggle = document.querySelector('.js-donation-banner-toggle');
    const details = document.querySelector('#donation-banner-details');
    const arrow = document.querySelector('.donation-banner__toggle-arrow');

    if (toggle && details && arrow) {
        toggle.addEventListener('click', function() {
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            
            if (isExpanded) {
                details.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            } else {
                details.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    }
})();
</script>
<?php endif; ?>