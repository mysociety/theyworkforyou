<div class="full-page legacy-page static-page">
    <div class="full-page__row">
        <div class="panel">
            <div class="main">
                <h1>Glossary index</h1>

                <?php include('_atoz.php'); ?>

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
