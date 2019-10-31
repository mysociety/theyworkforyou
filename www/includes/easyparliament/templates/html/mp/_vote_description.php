<li>
    <?= $description ?>
    <?php if ( $show_link ) { ?>
        <a class="vote-description__source" href="<?= $link ?>"><?= $link_text ?></a>
        <?php if (isset($key_vote)) { ?>
        <a class="vote-description__evidence" href="<?= $link ?>"><?= $key_vote['summary'] ?></a>
        <?php } ?>
    <?php } ?>
</li>
