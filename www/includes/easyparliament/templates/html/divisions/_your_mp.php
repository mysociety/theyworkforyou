<div class="debate-speech__division__your-mp">
  <?php if (!isset($main_vote_mp) || ! $main_vote_mp) { /* $main_vote_mp is true if an MP has been requested via the URL */ ?>
    <div class="your-mp__header">
      <h3>How your <?= $division['members']['singular'] ?> voted</h3>
      <p>
          Based on postcode <strong><?= $data['mp_data']['postcode'] ?></strong>
          <a href="<?= $data['mp_data']['change_url'] ?>">(Change postcode)</a>
      </p>
    </div>
  <?php } ?>
    <a href="<?= $data['mp_data']['mp_url'] ?>" class="your-mp__content">
      <?php if (isset($mp_vote)) { ?>
        <span class="your-mp__vote your-mp__vote--<?= $mp_vote['vote'] ?>"><?php
          switch ($mp_vote['vote']) {
              case 'aye':
                  echo 'Aye';
                  break;
              case 'no':
                  echo 'No';
                  break;
              case 'absent':
                  echo 'Absent';
                  break;
              case 'both':
                  echo 'Abstain';
                  break;
              case 'tellaye':
                  echo 'Aye (Teller)';
                  break;
              case 'tellno':
                  echo 'No (Teller)';
                  break;
              default:
                  echo 'N/A';
          }
          ?></span>
      <?php } elseif (isset($before_mp) || isset($after_mp)) { ?>
        <span class="your-mp__vote">N/A</span>
      <?php } ?>
        <img class="your-mp__image" src="<?= $data['mp_data']['image'] ?>">
        <div class="your-mp__person">
            <h2 class="people-list__person__name"><?= $data['mp_data']['name'] ?></h2>
            <p class="people-list__person__memberships">
                <span class="people-list__person__constituency"><?= $data['mp_data']['constituency'] ?></span>
                <span class="people-list__person__party <?= slugify($data['mp_data']['party']) ?>"><?= $data['mp_data']['party'] ?></span>
            </p>
        </div>
    </a>
  <?php if (isset($before_mp) || isset($after_mp)) { ?>
    <div class="your-mp__footer">
      <?php if (isset($before_mp)) { ?>
        <p>
            This vote happened before <a href="<?= $data['mp_data']['mp_url'] ?>"><?= $data['mp_data']['name'] ?></a> was elected.
        </p>
      <?php } elseif (isset($after_mp)) { ?>
        <p>
        This vote happened after <a href="<?= $data['mp_data']['mp_url'] ?>"><?= $data['mp_data']['name'] ?></a> left the <?= $assembly_name ?>.
        </p>
      <?php } ?>
    </div>
  <?php } ?>
</div>
