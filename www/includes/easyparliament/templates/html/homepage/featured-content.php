
<?php $featured_item = $annoucement_manager->get_random_valid_annoucement(); ?>

<?php if ( $featured_item ) { ?>
<h2>The latest</h2>
<div class="featured-content__wrapper">
<!-- TODO include image link and Alt text --> 
<img class="featured-content__image" src="<?= $featured_item->thumbnail_image_url ?>" alt="<?= $featured_item->thumbnail_image_alt_text ?>">
<div>
    <h3 class="featured-content__title"><?= $featured_item->title ?></h3>
    <p class="featured-content__description"><?= $featured_item->content ?></p>
    <a class="button featured-content__button" href="<?= $featured_item->url ?>"><?= (isset($featured_item->button_text) ? $featured_item->button_text : "Read more") ?></a>
</div>
</div>

<?php }; ?>