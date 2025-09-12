<?php
/**
 * Common entry display for register of interests
 * Variables expected:
 * - $entry: The entry object
 * - $register: The register object (for published_date)
 */
?>
<div class="interest-item <?= $entry->isNew($register->published_date) ? 'new_entry' : 'old_entry'; ?>" id="<?= $entry->comparable_id ?>" style="margin-bottom: 1.5rem;">
    <?php if ($entry->isNew($register->published_date)) { ?>
        <p>🆕<strong><?= gettext('New entry or subentry') ?></strong></p>
    <?php }; ?>
    <p><?= $entry->content ?></p>
    <?php if ($entry->hasEntryOrDetail()) { ?>
        <details style="margin-bottom: 1rem;">
            <summary>More details</summary>
            <br>
            <?php include INCLUDESPATH . 'easyparliament/templates/html/register/_register_entry.php'; ?>
        </details>
    <?php }; ?>
</div>