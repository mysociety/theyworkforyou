
<?php $featured_item = $announcement_manager->get_random_valid_item("sidebar"); ?>

<?php if ($featured_item) { ?>

<div class="sidebar__unit__featured_side">
  <div class="featured_side__mysociety">
      <img src="/style/img/logo-mysociety.png" alt="mySociety logo" width="150">
  </div>
  <img class="featured_side__image" src="<?= $featured_item->thumbnail_image_url ?>" alt="<?= $featured_item->thumbnail_image_alt_text ?>">
  <div class="featured_side__content">
  <a href="<?= $featured_item->url ?>"><h3 class="content__title"><?= $featured_item->title ?></h3></a>
  <?php if (isset($featured_item->button_text)) { ?>
  <a class="button content__button <?= $featured_item->button_class ?>" href="<?= $featured_item->url ?>"><?= ($featured_item->button_text ?? "Read more") ?></a>
  <?php } ?>
  </div>
</div>

<?php }; ?>
