<li>
    <?= $description ?>
    <?php if ( $show_link ) { ?>
        <a class="vote-description__source" href="<?= $link ?>"><?= isset($link_text) ? $link_text : 'Show votes' ?></a>
        <?php if (isset($key_vote)) { ?>
        <a class="vote-description__evidence" href="<?= $link ?>"><?= $key_vote['summary'] ?></a>
        <?php } ?>
    <?php } ?>
</li>
