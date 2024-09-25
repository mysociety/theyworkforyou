

<?php if ($this_page == "mp") { ?>

<?php if ($current_member[1]) { ?>

<div class="panel">
    <h2>About your Member of Parliament</h2>
    <p>
    Your MP (<?= ucfirst($full_name) ?>) represents you, and all of the people who live in <?= $latest_membership['constituency'] ?>,
        at the UK Parliament in Westminster.
    </p>
    <p>
    MPs split their time between Parliament and their constituency.
        In Parliament, they debate and vote on new laws, review existing laws, and question the Government.
        In the constituency, their focus is on supporting local people and championing local issues.
        They have a small staff team who help with casework, maintain their diaries, and monitor their inbox.
    </p>

<?php } else { ?>

<div class="panel">
    <h2>About your former Member of Parliament</h2>
    <p>
     <?= ucfirst($full_name) ?> is a former MP for <?= $latest_membership['constituency'] ?>.
    </p>
<?php } ?>

    <h2>
    What you can do
    </h2>
    <ul class="rep-actions">
        <li>Find out <a href="#profile">more about your MP</a>, including <a href="<?= $member_url ?>/votes">their voting summary</a><?php if ($register_interests) { ?>, <a href="<?= $member_url ?>/register">register of interests</a><?php } ?> and <a href="#appearances">recent speeches</a>.</li>
    <?php if ($current_member[1]) { ?>
        <li><a href="https://www.writetothem.com/">Write to your MP</a>, or find out about your other local representatives <a href="https://www.writetothem.com">on WriteToThem.com</a>.</li>
    <?php } ?>
    <?php if ($latest_membership['end_date'] == '2024-05-30') { ?>
        <li>Find out more about <a href="https://www.localintelligencehub.com/area/WMC/<?= $latest_membership['constituency'] ?>"><?= $latest_membership['constituency'] ?></a> on the <a href="https://www.localintelligencehub.com/">Local Intelligence Hub</a>.</li>
    <?php } elseif ($current_member[1]) { ?>
        <li>Find out more about <a href="https://www.localintelligencehub.com/area/WMC23/<?= $latest_membership['constituency'] ?>"><?= $latest_membership['constituency'] ?></a> on the <a href="https://www.localintelligencehub.com/">Local Intelligence Hub</a>.</li>
    <?php } ?>
    </ul>
</div>

<?php } ?>