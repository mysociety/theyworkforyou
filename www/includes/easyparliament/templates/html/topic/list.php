<div class="full-page topic-home">
    <div class="full-page__row">

        <h1>Topics</h1>

        <p class="topic-home__intro">TheyWorkForYou brings together information from a lot of different places,
        and can be hard to get started with or find what you're looking for. Topics
        bring together information about a specific subject.</p>

        <ul class="topic-list">

        <?php foreach ($topics as $page => $topic): ?>

            <?php $URL = new URL($page); ?>

            <li><a href="http://<?= DOMAIN ?><?= $URL->generate(); ?>">
                <img src="/images/<?= $page ?>.jpg">
                <?= _htmlspecialchars($topic); ?>
            </a></li>

        <?php endforeach; ?>

        </ul>

    </div>
</div>
