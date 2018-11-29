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
                <?php if ($main_vote_mp) { ?>
                    <?php if (isset($mp_vote)) { ?>
                    <p>
                        <span class="policy-vote__text">
                        <a href="/mp/?p=<?=  $member->person_id() ?>"><?= $member->full_name() ?></a> <?= preg_replace('/(voted\s+(?:for|against|not to|to|in favour))/', '<b>\1</b>', $mp_vote['text']) ?>
                        </span><br>
                    </p>
                    <?php } else if (isset($before_mp)) { ?>
                    <p>
                        This vote happened before <a href="/mp/?p=<?= $member->person_id() ?>"><?= $member->full_name() ?></a> was elected.
                    </p>
                    <?php } else if (isset($after_mp)) { ?>
                    <p>
                        This vote happened after <a href="/mp/?p=<?= $member->person_id() ?>"><?= $member->full_name() ?></a> left the House of Commons.
                    </p>
                    <?php } ?>
                    <p>
                    <?php if (!$division['has_description'] || !isset($mp_vote) || !in_array($mp_vote['vote'], array('aye', 'no'))) { ?>
                        <span class="policy-vote__text">
                            <?php include('_vote_description.php'); ?>
                        </span><br>
                    <?php } else { ?>
                        <?php if ($mp_vote['with_majority']) { ?>
                            A majority of MPs voted the same.
                        <?php } else { ?>
                            <?php $vote_prefix = 'A majority of MPs <b>disagreed</b> and'; include('_vote_description.php'); ?>
                        <?php } ?>
                    <?php } ?>
                    </p>

                    <?php include('_vote_summary.php'); ?>
                <?php } else { ?>
                    <p>
                        <span class="policy-vote__text">
                            <?php include('_vote_description.php'); ?>
                        </span><br>
                    </p>

                    <?php include('_vote_summary.php'); ?>

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
                    <?php } else if (isset($before_mp)) { ?>
                    <p>
                        This vote happened before your MP, <a href="/mp/?p=<?= $member->person_id() ?>"><?= $member->full_name() ?></a>, was elected.
                    </p>
                    <?php } else if (isset($after_mp)) { ?>
                    <p>
                        This vote happened after your MP, <a href="/mp/?p=<?= $member->person_id() ?>"><?= $member->full_name() ?></a>, left the House of Commons.
                    </p>
                    <?php } ?>
                <?php } ?>
                </div>
            </div>

             <ul class="debate-speech__meta debate-speech__links">
             <?php if (isset($division['debate_url'])) { ?>
                  <li class="link-to-speech">
                      <a class="link debate-speech__meta__link" href="<?= $division['debate_url'] ?>">Show full debate</a>
                  </li>
            <?php } ?>
            <?php if (isset($member) && isset($mp_vote)) { ?>
                <li>
                    <a class="internal-link debate-speech__meta__link" href="<?= $member->url() ?>/votes"><?= $member->full_name() ?>&rsquo;s full voting record</a>
                </li>
            <?php } ?>
            </ul>

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

    if ($division['absent'] > 0) {
        $vote_title = 'Absent';
        $votes = $division['absent_votes'];
        $anchor = 'absent';
        include '_vote_list.php';
    }

    if ($division['both'] > 0) {
        $vote_title = 'Abstained';
        $votes = $division['both_votes'];
        $anchor = 'both';
        include '_vote_list.php';
    }
?>
