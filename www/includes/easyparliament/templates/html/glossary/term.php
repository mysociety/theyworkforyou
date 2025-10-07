<div class="full-page legacy-page static-page">
    <div class="full-page__row">
        <div class="panel">
            <div class="main">
                <h1><?= $title ?></h1>

                <?php
                include('_atoz.php');
                if (isset($definition)) {
                    include('_item.php');
                }
                ?>

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
