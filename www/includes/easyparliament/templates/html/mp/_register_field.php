<?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Detail $detail */ ?>


    <?php if ($detail->type == "container"): ?>
        <li class="interest-detail">
        <span class="interest-detail-name"><?= $detail->display_as ?>: </span>

        <ul class="interest-detail-values-groups">
            <?php $upper_detail = $detail; ?>
            <?php foreach ($detail->sub_details() as $detail): ?>
                <?php include '_register_field.php'; ?>
            <?php endforeach; ?>
            <?php $detail = $upper_detail; ?>
        </ul>
        </li>
    <?php else : ?>
        <li class="interest-detail">
        <?php if ($detail->has_value()): ?>
            <span class="interest-detail-name"><?= $detail->display_as ?>: </span>
            <span class="interest-detail-value"><?= htmlspecialchars($detail->value) ?></span>
        <?php endif; ?>
        </li>
    <?php endif; ?>
