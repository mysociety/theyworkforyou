<li>
    <?= $description ?>
    <a class="vote-description__source" href="<?= $link ?>"><?= isset($link_text) ? $link_text : 'Show votes' ?></a>
    <?php if (isset($key_vote) || isset($party_voting_line)) { ?>
    <a class="vote-description__evidence" href="<?= $link ?>">
        <?= isset($key_vote) ? "$key_vote[summary]." : '' ?>
        <?= isset($party_voting_line) ? $party_voting_line : '' ?>
    </a>
    <?php } ?>
</li>
