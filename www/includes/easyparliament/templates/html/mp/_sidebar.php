<?php include '_person_navigation.php'; ?>
<?php include '_featured_content.php'; ?>

<?php $newsletter_item = $announcement_manager->get_random_valid_item("sidebar", "donation"); ?>
<?php include INCLUDESPATH . 'easyparliament/templates/html/sidebar/_donation.php'; ?>

<?php $newsletter_item = $announcement_manager->get_random_valid_item("sidebar", "newsletter"); ?>
<?php include INCLUDESPATH . 'easyparliament/templates/html/sidebar/_newsletter.php'; ?>
