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
          <h4>Thank you for your help</h4><p>Your definition for <strong>&quot;<?= $title ?>&quot;</strong> has been submitted and awaits moderator approval. If every thing is well and good, it should appear on the site within the next day or so.</p>
          <p>You can browse the exising glossary below:</p>

          <?= $PAGE->glossary_atoz($glossary) ?>
  
        <?php } elseif (isset($show_matches)) { ?>
            <h4>Found <?= $glossary->num_search_matches ?> <?= ngettext('match', 'matches', $glossary->num_search_matches) ?> for <em><?= $glossary->query ?></em></h4>
            <!-- XXX plurals -->
            <p>It seems we already have <?= ngettext('a definition', 'some definitions', $glossary->num_search_matches) ?> for that. Would you care to see <?= ngettext('it', 'them', $glossary->num_search_matches) ?>?</p>
            <ul class="glossary">
            <?php
              foreach ($glossary->search_matches as $match) {
                  $URL = new \MySociety\TheyWorkForYou\Url('glossary');
                  $URL->insert(['gl' => $match['glossary_id']]);
                  $URL->remove(['g']);
                  $term_link = $URL->generate('url');
                  ?>
                  <li>
                    <a href="<?= $term_link ?>"><?= $match['title']?></a>
                  </li>
            <?php } ?>
            </ul>
        <?php } elseif (isset($appearances)) { ?>
        <h4>So far so good</h4>

        <p>
          Just so you know, we found <strong><?= $appearances ?></strong> occurences of <strong><?= $term ?></strong> in Hansard.<br>
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
        <?php } elseif (isset($preview)) { ?>
          <h1>Add a glossary item</h1>

          <p>Your entry should look something like this:</p>

          <?php include("_item.php"); ?>

          <?php include("_addterm_form.php"); ?>

        <?php } elseif (!isset($submitted)) { ?>
            <p>Seen a piece of jargon or an external reference? By adding the phrase and definition to the glossary, you'll create a link for it everywhere an MP or Peer says it. Search for a phrase to add or browse the existing entries for inspiration.</p>
            <h3>Step 1: Search for a phrase</h3>

            <?php include("_searchterm_form.php"); ?>

            <p><small>Some examples:<br>
              A technical term, or a piece of jargon e.g. <em>&quot;<a href="<?= $example_urls['technical']['url'] ?>"><?= $example_urls['technical']['name'] ?></a>&quot;(671 occurences)</em><br>
              An external organisation e.g. <em>&quot;<a href="<?= $example_urls['organisation']['url'] ?>"><?= $example_urls['organisation']['name'] ?></a>&quot;(80 occurences)</em><br>
              An external web document e.g. <em>&quot;<a href="<?= $example_urls['document']['url'] ?>"><?= $example_urls['document']['name'] ?></a>&quot;(104 occurences)</em></small></p>
            <p>Or browse the existing entries:</p>

            <?= $PAGE->glossary_atoz($glossary) ?>
        <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>
