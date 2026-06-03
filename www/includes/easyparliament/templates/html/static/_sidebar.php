<?php $featured_item = $announcement_manager->get_random_valid_item("sidebar_interests", "donation"); ?>
<?php include INCLUDESPATH . 'easyparliament/templates/html/sidebar/_donation.php'; ?>

<?php $newsletter_item = $announcement_manager->get_random_valid_item("sidebar_interests", "newsletter"); ?>
<?php include INCLUDESPATH . 'easyparliament/templates/html/sidebar/_newsletter.php'; ?>
