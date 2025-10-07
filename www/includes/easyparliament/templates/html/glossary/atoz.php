<div class="full-page legacy-page static-page">
    <div class="full-page__row">
        <div class="panel">
            <div class="main">
                <h1>Glossary index</h1>
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

                        if ($letter == $glossary->current_letter) { ?>
                            <li class="on"><?= $letter ?></li>
                        <?php } elseif (!empty($eps)) { ?>
                            <li><a href="<?= $url->generate('url') ?>"><?= $letter ?></a></li>
                        <?php } else { ?>
                            <li><?= $letter ?></li>
                        <?php }
                        $count++;
                    } ?>
                    </ul>
                </div>

                <ul class="glossary">
                <?php
                $url->remove(['az']);
                foreach ($glossary->alphabet[$glossary->current_letter] as $glossary_id) {
                    $url->insert(['gl' => $glossary_id]);
                    ?>
                    <li><a href="<?= $url->generate('url') ?>"><?= $glossary->terms[$glossary_id]['title'] ?></a></li>
                <?php } ?>
                </ul>
            </div>
            <div class="sidebar">
                <?php if (isset($add_url)) { ?>
                    <p>
                        <a href="<?= $add_url ?>">Add entry</a>
                    </p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
