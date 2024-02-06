<li id="<?= $agreement['gid'] ?>">
    <span class="policy-vote__date">On <?= strftime('%e %b %Y', strtotime($agreement['date'])) ?>:</span>
    <span class="policy-vote__text"><?= $division['division_name'] ?></span>

    <a class="vote-description__source" href="<?= $division['url'] ?>">Show Decision</a>

</li>
