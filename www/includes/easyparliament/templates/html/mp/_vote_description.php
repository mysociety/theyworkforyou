<li>
    <?= $description ?>
    <?php if ( $show_link ) { ?>
        <a class="vote-description__source" href="<?= $link ?>">Show votes</a>
        <?php if (isset($key_vote) || isset($party_voting_line)) { ?>
        <a class="vote-description__evidence" href="<?= $link ?>">
            <?= isset($key_vote) ? "$key_vote[summary]." : '' ?>
            <?= isset($party_voting_line) ? $party_voting_line : '' ?>
        </a>
        <?php } ?>
    <?php } ?>
</li>
