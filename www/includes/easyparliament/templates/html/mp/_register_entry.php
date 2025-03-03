<?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\InfoEntry $entry */ ?>

<div class="interest-item" id="<?= $entry->comparable_id ?>">

<?php if (!$entry->details->isEmpty()) { ?>

    <?php if ($entry->content) { ?>
        <?php if ($entry->info_type == "subentry") { ?>
            <h6 class="interest-summary"><?= htmlspecialchars($entry->content) ?></h6>
        <?php } else { ?>
            <h4 class="interest-summary"><?= htmlspecialchars($entry->content) ?></h4>
        <?php }; ?>
    <?php }; ?>
        <ul class="interest-details-list">
            <?php foreach ($entry->details as $detail) { ?>
                <?php include '_register_field.php'; ?>
            <?php }; ?>

            <?php if ($entry->date_registered) { ?>
                <li class="registration-date interest-detail">Registration Date: <?= htmlspecialchars($entry->date_registered) ?></li>
            <?php }; ?>
            <?php if ($entry->date_published) { ?>
                <li class="published-date interest-detail">Published Date: <?= htmlspecialchars($entry->date_published) ?></li>
            <?php }; ?>
            <?php if ($entry->date_updated) { ?>
                <li class="last-updated-date interest-detail">Last Updated Date: <?= htmlspecialchars($entry->date_updated) ?></li>
            <?php }; ?>
        </ul>

    <?php } else { ?>
    <?php if ($entry->content) { ?>
        <?php // This is a more mininal style of entry, don't use the header structure, just print the xml content?>
        <?php if ($entry->content_format == "xml") { ?>
            <?= $entry->content ?>
        <?php } else { ?>
            <p class="interest-content"><?= htmlspecialchars($entry->content) ?></p>
        <?php }; ?>
    <?php }; ?>
<?php }; ?>

    <?php if (!$entry->sub_entries->isEmpty()) { ?>
        <h5 class="child-item-header">Specific work or payments</h5>
        <div class="interest-child-items" id="parent-<?= $entry->comparable_id ?>">
            <?php foreach ($entry->sub_entries as $subentry) {
                $parent_entry = $entry;
                $entry = $subentry;
                include '_register_entry.php';
                $entry = $parent_entry;
            } ?>
        </div>
    <?php }; ?>

</div>