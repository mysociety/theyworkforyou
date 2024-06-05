<div class="panel panel--secondary">
    <p><?= gettext('Note for journalists and researchers: The data on this page may be used freely, on condition that TheyWorkForYou.com is cited as the source.') ?></p>

    <p><?= gettext('This data was produced by TheyWorkForYou from a variety of sources.') ?></p>

    <p><?= gettext('For an explanation of the vote descriptions please see our page about <a href="/voting-information">voting information on TheyWorkForYou</a>.') ?></p>

  <?php if(isset($data['photo_attribution_text'])) { ?>
    <p>
      <?php if(isset($data['photo_attribution_link'])) { ?>
        <?= gettext('Profile photo:') ?>
        <a href="<?= $data['photo_attribution_link'] ?>"><?= $data['photo_attribution_text'] ?></a>
      <?php } else { ?>
        <?= gettext('Profile photo:') ?> <?= $data['photo_attribution_text'] ?>
      <?php } ?>
    </p>
  <?php } ?>
  <?php if(!$image || !$image['exists']) { ?>
    <p>
        We&rsquo;re missing a photo of <?= $full_name ?>.
        If you have a photo <em>that you can release under
        a Creative Commons Attribution-ShareAlike license</em>
        or can locate a <em>copyright free</em> photo,
        <a href="mailto:<?= str_replace('@', '&#64;', CONTACTEMAIL) ?>">please email it to us</a>.
        Please do not email us about copyrighted photos
        elsewhere on the internet; we can&rsquo;t use them.
    </p>
    <?php }; ?>
                  
</div>
