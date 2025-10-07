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

            <p>Seen a piece of jargon or an external reference? By adding the phrase and definition to the glossary, you'll create a link for it everywhere an MP or Peer says it. Search for a phrase to add or browse the existing entries for inspiration.</p>
            <h3>Step 1: Search for a phrase</h3>

            <?php include("_searchterm_form.php"); ?>

            <p><small>Some examples:<br>
              A technical term, or a piece of jargon e.g. <em>&quot;<a href="<?= $example_urls['technical']['url'] ?>"><?= $example_urls['technical']['name'] ?></a>&quot;(671 occurrences)</em><br>
              An external organisation e.g. <em>&quot;<a href="<?= $example_urls['organisation']['url'] ?>"><?= $example_urls['organisation']['name'] ?></a>&quot;(80 occurrences)</em><br>
              An external web document e.g. <em>&quot;<a href="<?= $example_urls['document']['url'] ?>"><?= $example_urls['document']['name'] ?></a>&quot;(104 occurrences)</em></small></p>
            <p>Or browse the existing entries:</p>

            <?php include('_atoz.php'); ?>
        </div>
      </div>
    </div>
  </div>
</div>
