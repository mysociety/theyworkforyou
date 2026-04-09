<?php if ($featured_item) { ?>
<h2><?= gettext('The latest') ?></h2>
<div class="featured-content__wrapper">
<?php if (isset($featured_item->thumbnail_image_url)) { ?>
    <a href="<?= $featured_item->url ?>"><img class="featured-content__image" src="<?= $featured_item->thumbnail_image_url ?>" alt="<?= $featured_item->thumbnail_image_alt_text ?>"></a>
<?php } ?>
<div>
    <a href="<?= $featured_item->url ?>"><h3 class="featured-content__title"><?= $featured_item->title ?></h3></a>
    <p class="featured-content__description"><?= $featured_item->content ?></p>
    <?php if (isset($featured_item->button_text)) { ?>
        <a class="button featured-content__button" href="<?= $featured_item->url ?>"><?= ($featured_item->button_text ?? "Read more") ?></a>
    <?php } ?>
</div>
</div>
<?php } ?>
