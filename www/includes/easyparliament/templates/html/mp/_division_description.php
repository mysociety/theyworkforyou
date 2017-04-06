<li id="<?= $division['division_id'] ?>" class="<?= $division['strong'] || $show_all ? 'policy-vote--major' : 'policy-vote--minor' ?>">
    <span class="policy-vote__date">On <?= strftime('%e %b %Y', strtotime($division['date'])) ?>:</span>
    <span class="policy-vote__text"><?= $full_name ?><?= $division['text'] ?></span>
    <?php if ( $division['url'] ) { ?>
        <a class="vote-description__source" href="<?= $division['url'] ?>">Show full debate</a>
    <?php } ?>
</li>

