<li id="<?= $division['division_id'] ?>" class="<?= $show_all || $division['strong'] ? 'policy-vote--major' : 'policy-vote--minor' ?>">
    <span class="policy-vote__date">On <?= strftime('%e %b %Y', strtotime($division['date'])) ?>:</span>
    <span class="policy-vote__text"><?= $full_name ?><?= $division['text'] ?></span>
    <?php if ($division['date'] > '2020-06-01' && $division['date'] < '2020-06-10' && $division['vote'] == 'absent') { ?>
        <p class="vote-description__covid">This absence may have been affected by <a href="#covid-19">COVID-19 restrictions</a>.</p> 
    <?php } ?>
    <a class="vote-description__source" href="/divisions/<?= $division['division_id'] ?>/mp/<?= $person_id ?>">Show vote</a>
</li>

