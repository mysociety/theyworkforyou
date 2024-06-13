

<?php $featured_item = $announcement_manager->get_random_valid_item("sidebar_right_donation"); ?>

<?php if ( $featured_item ) { ?>

<div class="sidebar__unit__featured_side sidebar__unit__featured_side--right">
  <div class="featured_side__content">
  <a href="<?= $featured_item->url ?>"><h3 class="content__title"><?= $featured_item->title ?></h3></a>
  <p class="content__description"><?= $featured_item->content ?></p>  
  <?php if (isset($featured_item->button_text)) { ?>
  <a class="button content__button <?= $featured_item->button_class ?>" href="<?= $featured_item->url ?>"><?= (isset($featured_item->button_text) ? $featured_item->button_text : "Read more") ?></a>
  <?php } ?>
  </div>
</div>

<?php }; ?>

<?php $featured_item = $announcement_manager->get_random_valid_item("sidebar_right_subscribe"); ?>

<?php if ( $featured_item ) { ?>

<div class="sidebar__unit__featured_side sidebar__unit__featured_side--right">
  <div class="featured_side__content">
  <a href="<?= $featured_item->url ?>"><h3 class="content__title"><?= $featured_item->title ?></h3></a>
  <p class="content__description"><?= $featured_item->content ?></p>  
  <?php if (isset($featured_item->button_text)) { ?>
  <a class="button content__button <?= $featured_item->button_class ?>" href="<?= $featured_item->url ?>"><?= (isset($featured_item->button_text) ? $featured_item->button_text : "Read more") ?></a>
  <?php } ?>
  </div>
</div>

<?php }; ?>
