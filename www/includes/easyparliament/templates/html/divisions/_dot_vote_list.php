<?php if (count($votes) > 0) { ?>
  <div class="division-section__vote division-section__vote__dots">
      <h2 id="<?= $anchor ?>" title="<?= $summary ?>"><?= $vote_title ?>: <?= $division[$anchor] ?> <?= $division[$anchor] == 1 ? $division['members']['singular'] : $division['members']['plural'] ?></h2>
      <ul class="division-dots">
        <?php foreach ($votes as $vote) { ?>
        <li class="people-list__person__party <?= slugify($vote['party']) ?>" title="<?= $vote['name']
            ?>, <?= gettext($vote['party'])
            ?><?= $vote['teller'] ? ' (teller)' : ''
            ?><?= $vote['proxy'] ? " (proxy vote cast by $vote[proxy])" : ''
            ?>"></li>
        <?php } ?>
      </ul>
  </div>
<?php } ?>
