<div class="panel panel--secondary">
    <p>Note for journalists and researchers: The data on this page may be used freely,
       on condition that TheyWorkForYou.com is cited as the source.</p>

    <p>For an explanation of the vote descriptions please see the FAQ entries on
    <a href="/help/#vote-descriptions">vote descriptions</a> and
    <a href="/help/#votingrecord">how the voting record is decided</a></p>

  <?php if(isset($data['photo_attribution_text'])) { ?>
    <p>
      <?php if(isset($data['photo_attribution_link'])) { ?>
        Profile photo:
        <a href="<?= $data['photo_attribution_link'] ?>"><?= $data['photo_attribution_text'] ?></a>
      <?php } else { ?>
        Profile photo: <?= $data['photo_attribution_text'] ?>
      <?php } ?>
    </p>
  <?php } ?>
</div>
