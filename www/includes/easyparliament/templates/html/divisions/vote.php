<?php $current_assembly = 'uk-commons'; ?>
<div class="debate-header regional-header regional-header--<?= $current_assembly ?>">
    <div class="regional-header__overlay"></div>
    <div class="full-page__row">
        <div class="debate-header__content full-page__unit">
              <h1><?= $division['division_title'] ?></h1>
            <p class="lead">
                Division number <?= $division['number'] ?> <?= $location ?>
                <?php if ($debate_time_human) { ?>at <?= $debate_time_human ?><?php } ?>
                on <?= $debate_day_human ?>.
            </p>
        </div>
    </div>
    <nav class="debate-navigation" role="navigation">
        <div class="full-page__row">
            <div class="full-page__unit">
                <div class="debate-navigation__pagination">
                    <?php if (isset($nextprev['prev'])) { ?>
                    <div class="debate-navigation__previous-debate">
                        <a href="<?= $nextprev['prev']['url'] ?>" rel="prev">&laquo; <?= $nextprev['prev']['body'] ?></a>
                    </div>
                    <?php } ?>

                    <?php if (isset($nextprev['up'])) { ?>
                    <div class="debate-navigation__all-debates">
                        <a href="<?= $nextprev['up']['url'] ?>" rel="up"><?= $nextprev['up']['body'] ?></a>
                    </div>
                    <?php } ?>

                    <?php if (isset($nextprev['next'])) { ?>
                    <div class="debate-navigation__next-debate">
                        <a href="<?= $nextprev['next']['url'] ?>" rel="next"><?= $nextprev['next']['body'] ?> &raquo;</a>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </nav>
</div>
<div class="debate-speech">
    <div class="full-page__row">
        <div class="full-page__unit">
            <div class="debate-speech__speaker-and-content">
                <div class="debate-speech__content">

                    <p>
                        <span class="policy-vote__text">
                            <?php include('_vote_description.php'); ?>
                        </span><br>
                    </p>

                    <p>
                        <a href="#for"><?= $division['for'] - 2 ?> for</a>, <a href="#against"><?= $division['against'] - 2 ?> against</a>, <a href="#both"><?= $division['both'] ?> abstained</a>, <a href="#absent"><?= $division['absent'] ?> absent</a>.
                    </p>

                    <?php if (isset($mp_vote)) { ?>
                    <p>
                      Your MP, <a href="/mp/?p=<?= $member->person_id() ?>"><?= $member->full_name() ?></a>,
                        <?php if ($mp_vote['vote'] == 'aye') { ?>
                            voted for.
                        <?php } else if ($mp_vote['vote'] == 'no') { ?>
                            voted against.
                        <?php } else if ($mp_vote['vote'] == 'absent') { ?>
                            was absent.
                        <?php } else if ($mp_vote['vote'] == 'both') { ?>
                            abstained.
                        <?php } else if ($mp_vote['vote'] == 'tellaye') { ?>
                            was a teller for the Ayes.
                        <?php } else if ($mp_vote['vote'] == 'tellno') { ?>
                            was a teller for the Nos.
                        <?php } ?>
                    </p>
                    <?php } ?>
                </div>
            </div>

            <?php if ( $division['debate_url'] ) { ?>
             <ul class="debate-speech__meta debate-speech__links">
                  <li class="link-to-speech">
                      <a class="link debate-speech__meta__link" href="<?= $division['debate_url'] ?>">Show full debate</a>
                  </li>
            </ul>
            <?php } ?>

        </div>
    </div>
</div>

<?php
    $vote_title = 'Aye';
    $anchor = 'for';
    $votes = $division['yes_votes'];
    include '_vote_list.php';

    $vote_title = 'No';
    $anchor = 'against';
    $votes = $division['no_votes'];
    include '_vote_list.php';

    $vote_title = 'Absent';
    $votes = $division['absent_votes'];
    $anchor = 'absent';
    include '_vote_list.php';

    $vote_title = 'Abstained';
    $votes = $division['both_votes'];
    $anchor = 'both';
    include '_vote_list.php';
?>
