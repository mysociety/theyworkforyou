

<?php $featured_item = $annoucement_manager->get_random_valid_item("donation"); ?>

<?php if ( $featured_item ) { ?>

<div class="sidebar__unit__featured_side">
  <div class="featured_side__content">
  <a href="<?= $featured_item->url ?>"><h3 class="content__title"><?= $featured_item->title ?></h3></a>
  <p class="content__description"><?= $featured_item->content ?></p>  
  <a class="button content__button <?= $featured_item->button_class ?>" href="<?= $featured_item->url ?>"><?= (isset($featured_item->button_text) ? $featured_item->button_text : "Read more") ?></a>
  </div>
</div>

<?php }; ?>