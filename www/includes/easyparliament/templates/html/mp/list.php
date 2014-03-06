<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">

            <p>That name is not unique. Please select from the following:</p>

            <ul>

                <?php foreach ($mps as $mp): ?>
                <li><a href="<?= $mp['url'] ?>"><?= $mp['name'] ?></a></li>
                <?php endforeach; ?>

            </ul>

            <p><a href="<?= $all_mps_url ?>">Browse all MPs</a></p>

        </div>
    </div>
</div>
