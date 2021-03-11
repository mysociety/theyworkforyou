    <div class="full-page__row">
        <div class="business-section">
          <div class="business-section__header">
              <h1 class="business-section__header__title">
              Recent Votes
              </h1>
          </div>
          <div class="business-section__primary">
               <?php
               if ( isset($divisions) && $divisions ) {
                 $current_date = '';
               ?>
               <ul class="business-list">
                    <?php foreach ($divisions as $division) { ?>
                    <li id="<?= $division['division_id'] ?>">
                        <?php if ($current_date != $division['date']) { ?>
                        <span class="business-list__title">
                        <h3><?= format_date($division['date'], LONGERDATEFORMAT) ?></h3>
                        </span>
                        <?php
                          $current_date = $division['date'];
                        } ?>
                        <a href="/divisions/<?= $division['division_id'] ?>" class="business-list__title">
                            <h3><?= $division['division_title'] ?></h3>
                            <span class="business-list__meta">Division number <?= $division['number'] ?></span>
                        </a>
                        <p class="business-list__excerpt">
                            <?php include('_vote_description.php'); ?>
                            <br>
                            <span class="business-list__meta">
                                <?=$division['for'] ?> for,
                                <?=$division['against'] ?> against<?php
                                if ($division['both'] > 0) { ?>, <?=$division['both'] ?> abstained<?php }
                                if ($division['absent'] > 0) { ?>, <?=$division['absent'] ?> absent<?php } ?>.
                                <?= $division['mp_vote'] !== '' ? '<b>Your MP, ' . $mp_name . ', ' . $division['mp_vote'] . '.</b>' : '' ?>
                            </span>
                        </p>
                    </li>
                    <?php } ?>
               </ul>
               <?php } ?>
            </div>
            <div class="business-section__secondary">
                <div class="business-section__secondary__item">
                    <h3>What is this?</h3>

                    <p>This list contains votes from the House of Commons, House of Lords, Public Bill Committees, and the Scottish Parliament.</p>

                    <p class="voting-information-provenance">
                        Some vote information from <a href="https://www.publicwhip.org.uk/">PublicWhip</a>.
                        Last updated: <?= $last_updated ?>.
                        <a href="/voting-information">Please share these votes responsibly.</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
