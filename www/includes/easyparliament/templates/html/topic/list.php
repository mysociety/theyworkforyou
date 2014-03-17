<div class="full-page">
    <div class="full-page__row">

        <h2>Topics</h2>

        <p>TheyWorkForYou brings together information from a lot of different places,
        and can be hard to get started with or find what you're looking for. Topics
        bring together information about a specific subject.</p>

        <ul>

        <?php foreach ($topics as $page => $topic): ?>

            <?php $URL = new URL($page); ?>

            <li><a href="http://<?= DOMAIN ?><?= $URL->generate(); ?>"><?= htmlspecialchars($topic); ?></a></li>

        <?php endforeach; ?>

        </ul>

    </div>
</div>
