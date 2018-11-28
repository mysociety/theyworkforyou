<div class="debate-speech">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="debate-speech__speaker-and-content">
                <div class="debate-speech__content">
                    <h2 id="<?= $anchor ?>"><?= $vote_title ?></h2>
                    <?php if ($votes) {
                        $tellers = array();
                    ?>
                    <ul class="division-dots">
                        <?php foreach ($votes as $vote) { ?>
                          <li class="people-list__person__party <?= slugify($vote['party']) ?>"></li>
                        <?php } ?>
                    </ul>
                    <ul class="division-list">
                        <?php foreach ($votes as $vote) {
                          $voter = sprintf('<a href="/mp/?p=%d">%s</a>', $vote['person_id'], $vote['name']);
                          if ($vote['teller']) {
                              $tellers[] = $voter;
                          } else { ?>
                            <li><?= $voter ?></li>
                        <?php
                            }
                        } ?>
                    </ul>
                    <?php if (count($tellers) > 0) { ?>
                    <p>
                      Tellers: <?= implode(', ', $tellers) ?>
                    </p>
                    <?php }
                    } else { ?>
                    <p>
                    None
                    </p>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
