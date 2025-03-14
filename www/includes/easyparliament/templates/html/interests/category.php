
<div class="full-page static-page toc-page">
    <div class="full-page__row">

        <div class="toc-page__col">
        <?php if ($selected_category_id) { ?>
        <div class="toc js-toc">
            <ul>
            <li><a href="?>&chamber=<?= $chamber_slug ?>"><?= gettext("Back to categories list")?></a></li>

                <?php foreach ($categories as $category_id => $category_name) { ?>
                    <li><a href="?chamber=<?= $chamber_slug ?>&category_id=<?= $category_id ?>"><?= $category_name ?></a></li>
                <?php }; ?>
            </ul>
        </div>
        <?php }; ?>

        </div>
        <div class="toc-page__col">

            <div class="panel">
                <h1>📒<?= gettext('Register of interests')?></h1>
                <h2><?= $register->displayChamber() ?> - <?= $register->published_date ?></h2>
                <p><?= gettext('This page shows the latest version of the register of interests by category.') ?></p>
                <?php if (LANGUAGE == 'cy') { ?>
                    <p><?= gettext('For more information, see the official Senedd page') ?></a>.
                <?php } else { ?>
                    <p>For more information on the different categories, see the <a href="<?= $register->officialUrl() ?>">the official <?= $register->displayChamber() ?> page</a>.
                <?php } ?>
                <p><a href="/interests/"><?= gettext('Read more about our registers data')?></a>.</p>
                <hr>
                <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Register $register */ ?>

                <?php if ($selected_category_id === null) { ?>
                    <h3><?= gettext('Categories') ?></h3>
                    <ul>
                        <?php foreach ($categories as $category_id => $category_name) { ?>
                            <li><a href="?chamber=<?= $chamber_slug ?>&category_id=<?= $category_id ?>"><?= $category_name ?></a></li>
                        <?php }; ?>
                    </ul>
                <?php }; ?>


                <?php foreach ($categories as $category_id => $category_name) { ?>
                    <?php if ($selected_category_id != $category_id) {
                        continue;
                    }; ?>
                    <h2 id="category-<?= $category_id ?>"><?= $category_emojis[$selected_category_id] ?><?= $category_name ?></h2>
                    <div style="display: flex; gap: 1rem;">
                    <button id="detailsToggleButton" onclick="toggleDetails()" style="display:none">Expand All</button>
                    <button id="newToggleButton" onclick="toggleNewDetails()"; style="display:none">Just New</button>
                </div>
                    <?php foreach ($register->persons as $person) { ?>
                        <?php foreach ($person->categories as $person_category) { ?>
                        <?php if ($person_category->category_id != $selected_category_id || $person_category->only_null_entries()) {
                            continue;
                        }; ?> 
                        <div class="person-items <?php if ($person_category->only_old_entries($register->published_date)) { ?>old_entry<?php } ?>">
                        <h3><a href="/mp/<?= $person->intId() ?>/register"><?= $person->person_name ?></a></h3>
                        <?php foreach ($person_category->entries as $entry) { ?>
                                <div class="interest-item <?= $entry->isNew($register->published_date) ? 'new_entry' : 'old_entry'; ?>" id="<?= $entry->comparable_id ?>">
                                <?php if ($entry->isNew($register->published_date)) { ?>
                                    <p>🆕<strong><?= gettext('New entry or subentry') ?></strong></p>
                                <?php }; ?>
                                <p><?= $entry->content ?>
                                <p> 
                                <?php if ($entry->hasEntryOrDetail()) { ?>
                                <details>
                                <summary>More details</summary>
                                <br>
                                <?php include('_register_entry.php'); ?>
                                </details></p>
                                <?php }; ?>
                                </div>
                        <?php }; ?>
                        <hr/>
                        </div>
                        <?php }; ?>
                    <?php }; ?>
                <?php }; ?>

            </div>
        </div>  

    </div>
</div>

<script>

if (document.querySelectorAll('details').length > 0) {
    document.getElementById('detailsToggleButton').style.display = 'block';
}

if (document.querySelectorAll('.new_entry').length > 0) {
    document.getElementById('newToggleButton').style.display = 'block';
}

new_entries_only = false;

function toggleNewDetails() {
    // this 
    const old_entries = document.querySelectorAll('.old_entry');

    new_entries_only = !new_entries_only;
    if (new_entries_only) {
        old_entries.forEach(d => d.style.display = 'none');
        document.getElementById('newToggleButton').textContent = 'Show all';
    } else {
        old_entries.forEach(d => d.style.display = 'block');
        document.getElementById('newToggleButton').textContent = 'Just new';
    }

}


function toggleDetails() {
  const details = document.querySelectorAll('details');
  
  // Determine if all details are currently open
  let allOpen = true;
  details.forEach(d => {
    if (!d.open) {
      allOpen = false;
    }
  });

  if (allOpen) {
    // If all are open, close them
    details.forEach(d => d.open = false);
    document.getElementById('detailsToggleButton').textContent = 'Expand All';
  } else {
    // If at least one is closed, open them all
    details.forEach(d => d.open = true);
    document.getElementById('detailsToggleButton').textContent = 'Collapse All';
  }
}
</script>