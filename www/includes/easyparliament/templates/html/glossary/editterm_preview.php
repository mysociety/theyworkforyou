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

          <h1>Edit a glossary item</h1>

          <p>Your entry should look something like this:</p>

          <?php include("_item.php"); ?>

          <?php include("_editterm_form.php"); ?>
        </div>
      </div>
    </div>
  </div>
</div>
