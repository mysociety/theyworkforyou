<div class="full-page">
    <div class="full-page__row">
	<div class="panel">

            <?= $members_statement ?>

            <ul>

                <?php foreach ($members as $member) { ?>
                <li><a href="<?= $member['url'] ?>"><?= $member['name'] ?></a></li>
                <?php } ?>

            </ul>

        </div>
    </div>
</div>
