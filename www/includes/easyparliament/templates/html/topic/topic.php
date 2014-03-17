<div class="topic-header">
    <div class="full-page">
        <div class="full-page__row">
            <div class="topic-header__content page-content__row">
                <div class="topic-name">
                    <h1><?= $title ?></h1>
                    <h1 class="subheader">&amp; the UK Parliament</h1>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="topic-panels">

                <p class="lead"><?= $blurb ?></p>

                <p class="lead">Here are some places you might want to start:</p>

                <ul class="small-block-grid-2">

                <?php foreach ($actions as $action): ?>

                    <li>

                        <div class="panel">

                            <h3><a href="<?= $action['href'] ?>"><?= $action['title'] ?></a></h3>

                            <p><?= $action['blurb'] ?></p>

                        </div>

                    </li>

                <?php endforeach; ?>

                </ul>

            </div>
        </div>
    </div>
</div>
