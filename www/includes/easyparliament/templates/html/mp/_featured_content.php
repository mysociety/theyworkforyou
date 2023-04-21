
<?php $featured_item = $annoucement_manager->get_random_valid_item("sidebar"); ?>

<?php if ( $featured_item ) { ?>

<div class="sidebar__unit__featured_side">
  <img class="featured_side__image" src="<?= $featured_item->thumbnail_image_url ?>" alt="<?= $featured_item->thumbnail_image_alt_text ?>">
  <div class="featured_side__content">
      <h3 class="content__title"><?= $featured_item->title ?></h3>
      <p class="content__description"><?= $featured_item->content ?></p>
      <a class="button content__button" href="<?= $featured_item->url ?>"><?= (isset($featured_item->button_text) ? $featured_item->button_text : "Read more") ?></a>
  </div>
</div>

<?php }; ?>
