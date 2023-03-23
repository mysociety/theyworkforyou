<div class="panel panel--secondary">
    <p><?= gettext('Note for journalists and researchers: The data on this page may be used freely, on condition that TheyWorkForYou.com is cited as the source.') ?></p>

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
</div>
