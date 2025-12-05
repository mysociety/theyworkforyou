<?php if ($whip_removal_info && $whip_removal_info['has_whip_removed']): ?>
<div class="panel panel--highlight">
    <h3><?php printf(gettext('Why is %s listed as an independent %s?'), _htmlentities($full_name), $latest_membership['rep_name']); ?></h3>
    <p>
        <?php printf(gettext('%s is listed as an independent %s because their party (%s) suspended them from the parliamentary party ("removed the whip").'), $full_name, $latest_membership['rep_name'], $whip_removal_info['previous_party']); ?>
    </p>
    <p>
        <?= gettext('The whip can be removed for various reasons, including voting against the party on key issues, breaches of party rules, or other disciplinary matters.') ?>
    </p>
    <?php if (!empty($whip_removal_info['source'])): ?>
    <p>
        <a href="<?= _htmlentities($whip_removal_info['source']) ?>"><?php printf(gettext('Read more about the circumstances %s had the whip removed.'), _htmlentities($full_name)); ?></a>
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>