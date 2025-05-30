    <div class="full-page__row">
        <div class="business-section">
          <div class="business-section__header">
              <h1 class="business-section__header__title">
              <?= gettext('Recent Votes') ?>
              </h1>
          </div>
          <div class="business-section__primary">
               <?php
                if (isset($divisions) && $divisions) {
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
                            <span class="business-list__meta"><?= sprintf(gettext('Division number %s'), $division['number']) ?></span>
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
                    <h3><?= gettext('What is this?') ?></h3>
                    <?php if ($houses == 'commons') { ?>
                    <p><?= gettext('This list contains votes from the House of Commons.') ?></p>
                    <?php } elseif ($houses == 'lords') { ?>
                    <p><?= gettext('This list contains votes from the House of Lords.') ?></p>
                    <?php } elseif ($houses == 'pbc') { ?>
                    <p><?= gettext('This list contains votes from Public Bill Committees.') ?></p>
                    <?php } elseif ($houses == 'scotland') { ?>
                    <p><?= gettext('This list contains votes from the Scottish Parliament.') ?></p>
                    <?php } elseif ($houses == 'senedd') { ?>
                    <p><?= gettext('This list contains votes from the Senedd.') ?></p>
                    <?php } else { ?>
                    <p><?= gettext('This list contains votes from the House of Commons, House of Lords, Public Bill Committees, Senedd, and the Scottish Parliament.') ?></p>
                    <?php } ?>

                    <p><?= gettext('Only show votes from:') ?></p>
                    <ul>
                        <li><a href="?house=commons"><?= gettext('House of Commons') ?></a>
                        <li><a href="?house=lords"><?= gettext('House of Lords') ?></a>
                        <li><a href="?house=pbc"><?= gettext('Public Bill committees') ?></a>
                        <li><a href="?house=scotland"><?= gettext('Scottish Parliament') ?></a>
                        <li><a href="?house=senedd"><?= gettext('Senedd') ?></a>
                    </ul>

                    <p class="voting-information-provenance">
                        <?= gettext('Last updated:') ?> <?= $last_updated ?>.
                        <a href="/voting-information"><?= gettext('Learn more about our voting records and what they mean.') ?></a>
                    </p>
                </div>
                <?php include dirname(__FILE__) . '/../announcements/_sidebar_right_announcements.php'; ?>
            </div>
        </div>
    </div>
