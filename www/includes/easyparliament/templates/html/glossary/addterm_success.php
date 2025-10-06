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

          <h4>Thank you for your help</h4><p>Your definition for <strong>&quot;<?= $title ?>&quot;</strong> has been submitted and awaits moderator approval. If every thing is well and good, it should appear on the site within the next day or so.</p>

          <p>You can browse the exising glossary below:</p>

          <?= $PAGE->glossary_atoz($glossary) ?>
  
        </div>
      </div>
    </div>
  </div>
</div>
