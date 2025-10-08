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
<?php if (isset($edit_url)) { ?>
    <p>
        <a href="<?= $edit_url ?>">Edit entry</a>
    </p>
<?php } ?>
<?php if (isset($admin_url)) { ?>
    <p>
        <a href="<?= $admin_url ?>">Manage entries</a>
    </p>
<?php } ?>
