<div class="full-page legacy-page static-page">
  <div class="full-page__row">
    <div class="panel">
      <div class="stripe-side">
        <div class="main">
        <?php if (isset($error)) { ?>
          <p>
            <?= $error ?>
          </p>
        <?php } ?>

            <h4><?= $title ?></h4>
            <?php include("_editterm_form.php"); ?>
        </div>
      </div>
    </div>
  </div>
</div>
