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

        <?php if (isset($success)) { ?>
          <h4>Updated</h4>
  
          <p>
          The entry for <strong>&quot;<?= $title ?>&quot;</strong> has been updated.
          </p>
        <?php } elseif (isset($preview)) { ?>
          <h1>Add a glossary item</h1>

          <p>Your entry should look something like this:</p>

          <?php include("_item.php"); ?>

          <?php include("_editterm_form.php"); ?>

        <?php } elseif (!isset($submitted) && !isset($no_term)) { ?>
            <h4><?= $title ?></h4>
            <?php include("_editterm_form.php"); ?>
        <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>
