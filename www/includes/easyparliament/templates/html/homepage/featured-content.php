<?php global $featured_debate_shown; ?>
<?php $featured_item = $announcement_manager->get_random_valid_item("homepage"); ?>

<?php include '_featured_announcement.php'; ?>

<?php if (!$featured_item) { ?>
    <?php $featured_debate_shown = true; ?>
    <?php if (count($featured) > 0) {
        include 'featured.php';
    } else { ?>
                        No debates found.
    <?php } ?>
<?php }; ?>
