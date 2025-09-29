<?php if (!isset($notitle)) { ?>
<h3><?= $title ?></h3>
<?php } ?>
<p class="glossary-body">
  <?= $definition ?>
</p>
<?php if ($contributing_user) { ?>
  <p>
  <small>contributed by user <?= $contributing_user ?></small>
  </p>
<?php } ?>
