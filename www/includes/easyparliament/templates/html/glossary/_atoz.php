<div class="letters">
    <ul>
    <?php

    $url = new \MySociety\TheyWorkForYou\Url('glossary');
    $count = 0;
    foreach ($glossary->alphabet as $letter => $eps) {
        if ($count == 13) { ?>
            </ul>
            </div>
            <div class="letters">
            <ul>
        <?php }
        $url->insert(['az' => $letter]);

        if ($letter == $glossary->current_letter) {
            if ($glossary->current_term) { ?> 
            <li class="on"><a href="<?= $url->generate('url') ?>"><?= $letter ?></a></li>
            <?php } else { ?>
            <li class="on"><?= $letter ?></li>
            <?php } ?>
        <?php } elseif (!empty($eps)) { ?>
            <li><a href="<?= $url->generate('url') ?>"><?= $letter ?></a></li>
        <?php } else { ?>
            <li><?= $letter ?></li>
        <?php }
        $count++;
    } ?>
    </ul>
</div>
