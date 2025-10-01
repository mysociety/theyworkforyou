<div class="full-page legacy-page static-page">
    <div class="full-page__row">
        <div class="panel">
            <div class="main">
                <?php if (isset($title)) { ?>
                    <h1><?= $title ?></h1>
                <?php } else {?>
                    <h1>Glossary index</h1>
                <?php } ?> 
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
                        if ($glossary->current_term != "") { ?>
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
                }
                ?>
                        </ul>
                    </div>

                <?php if ($term) {
                    include('_item.php');
                } ?>

                <?php if (!$term && isset($glossary->terms)) { ?>
                    <ul class="glossary">
                    <?php foreach ($glossary->alphabet[$glossary->current_letter] as $glossary_id) {
                        $url->insert(['gl' => $glossary_id]);
                        ?>
                        <li><a href="<?= $url->generate('url') ?>"><?= $glossary->terms[$glossary_id]['title'] ?></a></li>
                        <?php } ?>

                    </ul>
                <?php } ?>
                </div>
                <div class="sidebar">
                <?php if (isset($nextprev)) { ?>
                    <p class="nextprev">
                        <span class="next"><a href="<?= $nextprev['next']['url'] ?>" title="Next term" class="linkbutton"><?= $nextprev['next']['body'] ?> »</a></span>
                        <span class="prev"><a href="<?= $nextprev['prev']['url'] ?>" title="Previous term" class="linkbutton">« <?= $nextprev['prev']['body'] ?></a></span>
                    </p>
                <?php } ?>

                <?php if (isset($add_url)) { ?>
                    <p>
                        <a href="<?= $add_url ?>">Add entry</a>
                    </p>
                <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
