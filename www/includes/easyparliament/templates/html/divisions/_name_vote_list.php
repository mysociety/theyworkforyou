<?php

if (!function_exists('print_voter')) {
    function print_voter($vote) {
        echo sprintf(
            '<li><a href="/mp/?p=%d">%s</a> <span class="party">%s%s</span></li>',
            $vote['person_id'],
            $vote['name'],
            gettext($vote['party']),
            $vote['proxy'] ? " (proxy vote cast by $vote[proxy])" : ''
        );
    }
}

if (count($votes) > 0) { ?>
  <div class="division-section__vote division-section__vote__names">
      <h3><?= $vote_title ?>: <?= gettext('A-Z by last name') ?></h3>
      <?php $tellers = []; ?>
      <ul class="division-names js-vote-accordion" data-show-all="<?= gettext('Show All') ?>">
        <?php foreach ($votes as $vote) {
            if ($vote['teller']) {
                $tellers[] = $vote;
            } else {
                print_voter($vote);
            }
        } ?>
      </ul>
    <?php if (count($tellers) > 0) { ?>
      <h4><?= gettext('Tellers') ?></h4>
      <ul class="division-names">
        <?php foreach($tellers as $teller) {
            print_voter($teller);
        } ?>
      </p>
    <?php } ?>
  </div>
<?php } ?>
