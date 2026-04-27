<?php
/**
 * Common entry display for register of interests
 * Variables expected:
 * - $entry: The entry object
 * - $register: The register object (for published_date)
 * - $entry_permalink_chamber_slug: (optional) permalink chamber slug
 * - $entry_permalink_category_id: (optional) permalink category ID
 * - $entry_permalink_date: (optional) permalink register date
 */

$entry_permalink_args = [
    $entry_permalink_chamber_slug ?? null,
    $entry_permalink_category_id ?? null,
    $entry_permalink_date ?? null,
];
$show_entry_permalink = count(array_filter($entry_permalink_args)) === 3;
?>
<div class="interest-item <?= $entry->isNew($register->published_date) ? 'new_entry' : 'old_entry'; ?>" id="<?= $entry->comparable_id ?>" style="margin-bottom: 1.5rem;">
    <?php if ($entry->isNew($register->published_date)) { ?>
        <p>🆕<strong><?= gettext('New entry or subentry') ?></strong></p>
    <?php }; ?>
    <p><?= $entry->content ?><?php if ($show_entry_permalink) { ?> <a class="interest-permalink" href="<?= htmlspecialchars($entry->categoryPageUrl($entry_permalink_chamber_slug, (string) $entry_permalink_category_id, $entry_permalink_date)) ?>" title="<?= gettext('Permalink to this entry') ?>">🔗</a><?php } ?></p>
    <?php if ($entry->hasEntryOrDetail()) { ?>
        <details style="margin-bottom: 1rem;">
            <summary>More details</summary>
            <br>
            <?php include INCLUDESPATH . 'easyparliament/templates/html/register/_register_entry.php'; ?>
        </details>
    <?php }; ?>
</div>