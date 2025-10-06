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

        <h4>So far so good</h4>

        <p>
          Just so you know, we found <strong><?= $appearances ?></strong> occurrences of <strong><?= $term ?></strong> in Hansard.<br>
          To make sure that your definition will not appear out of context, please have a look at the <a href="#excerpts">excerpts</a>. If you're happy that your definition will apply to the right thing, then carry on below:
        </p>

          <a id='definition'></a>
          <p>Please add your definition below:</p>
          <h4>Add a definition for <em><?= $term ?></em></h4>

          <?php include("_addterm_form.php"); ?>

          <a id="excerpts"></a>
          <h4>Contextual excerpts</h4>
          <dl id="searchresults">
          <?php foreach ($examples as $example) { ?>
            <dt>
              <a href="<?= $example['listurl'] ?>">
              <?php if (isset($example['parent']) && count($example['parent']) > 0) { ?>
                <strong><?= $example['parent']['body'] ?></strong>
              <?php } ?>
              </a><small>(<?= format_date($example['hdate'], SHORTDATEFORMAT) ?>)</small>
            </dt>
            <dd>
              <p>
                <?= $example['extract'] ?>
              </p>
            </dd> 
            
          <?php } ?>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>
