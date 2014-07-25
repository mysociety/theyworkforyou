<div class="debate-header">
    <div class="full-page__row">
        <div class="debate-header__content">
            <h1><?= $heading ?></h1>
            <p class="lead">
                <?= $intro ?> <?= $location ?><br>at <?= $debate_time_human ?> on
                <a href="<?= $debate_day_link ?>"><?= $debate_day_human ?></a>.
            </p>
            <p class="cta">
              <?php if(isset($full_debate_url)){ ?>
                <a class="button subtle" href="<?= $full_debate_url ?>">Show full debate</a>
              <?php } ?>
                <a class="button alert" href="/alerts/?alertsearch=<?= urlencode($email_alert_text) ?>">Alert me about debates like this</a>
            </p>
        </div>
    </div>
</div>
<div class="full-page">

  <?php foreach($speeches['rows'] as $speech){ ?>

    <?php

    if ($speech['htype'] == '10' || $speech['htype'] == '11') {
        continue; // This is a heading, not a speech. Ignore it.
    }

    if (isset($speech['source_url'])) {
        $source_url = $speech['source_url'];
        if ($speeches['info']['major']==1 ||
            $speeches['info']['major']==2 ||
            (
                ($speeches['info']['major']==3 || $speeches['info']['major']==4) &&
                isset($speech['speaker']['house'])
            ) ||
            $major==101 ||
            $major==6
        ) {
            $source_title = 'Citation: ';
            if ($speeches['info']['major']==1 || $speeches['info']['major']==2) {
                $source_title .= 'HC';
            } elseif ($speeches['info']['major']==3 || $speeches['info']['major']==4) {
                if ($speech['speaker']['house']==1) {
                    $source_title .= 'HC';
                } else {
                    $source_title .= 'HL';
                }
            } elseif ($speeches['info']['major']==6) {
                $source_title .= $section['title'];
            } else {
                $source_title .= 'HL';
            }
            $source_title .= ' Deb, ' . format_date($speeches['info']['date'], LONGDATEFORMAT) . ', c' . $speech['colnum'];
            if ($speeches['info']['major']==2) {
                $source_title .= 'WH';
            } elseif ($speeches['info']['major']==3) {
                $source_title .= 'W';
            } elseif ($speeches['info']['major']==4) {
                $source_title .= 'WS';
            }
        }
    }

    ?>

    <div class="debate-speech" id="g<?= gid_to_anchor($speech['gid']) ?>">
        <div class="full-page__row">
            <a name="g<?= gid_to_anchor($speech['gid']) ?>"></a>
          <?php if(isset($speech['speaker']) && count($speech['speaker']) > 0) { ?>
            <h2 class="debate-speech__speaker">
                <?php

                $speaker_name = ucfirst(member_full_name(
                    $speech['speaker']['house'],
                    $speech['speaker']['title'],
                    $speech['speaker']['first_name'],
                    $speech['speaker']['last_name'],
                    $speech['speaker']['constituency']
                ));

                list($image_url, $size) = find_rep_image(
                    $speech['speaker']['person_id'],
                    true,
                    $speeches['info']['major'] == 101 ? 'lord' : 'general'
                );

                if (isset($speech['speaker']['office'])) {
                    $speaker_position = $speech['speaker']['office'][0]['pretty'];
                } else {
                    $speaker_position = htmlentities($speech['speaker']['party']);
                    if ($speech['speaker']['house'] == 1 &&
                        $speech['speaker']['party'] != 'Speaker' &&
                        $speech['speaker']['party'] != 'Deputy Speaker' &&
                        $speech['speaker']['constituency']
                    ) {
                        $speaker_position .= ' MP, ' . $speech['speaker']['constituency'];
                    }
                }

                ?>
                <a href="<?= $speech['speaker']['url'] ?>">
                    <img src="<?= $image_url ?>" alt="Photo of <?= $speaker_name ?>">
                    <strong class="debate-speech__speaker__name"><?= $speaker_name ?></strong>
                    <small class="debate-speech__speaker__position"><?= $speaker_position ?></small>
                </a>
            </h2>
          <?php } ?>
            <div class="debate-speech__content">
                <?= annotate_speech($speech['body'], $speeches['info']['glossarise']); ?>
            </div>
            <ul class="debate-speech__meta">
              <?php if (!isset($previous_speech_time) || $previous_speech_time != $speech['htime']){ ?>
                <li class="time">
                    <a href="<?= $speech['listurl'] ?>">
                        <?= format_time($speech['htime'], 'g:i a') ?>,
                        <?= format_date($speech['hdate'], 'jS F Y') ?>
                    </a>
                </li>
              <?php } ?>
                <li class="link-to-speech"><a href="<?= $speech['listurl'] ?>">Link to this speech</a></li>
                <li class="link-to-hansard"><a href="<?= $source_url ?>" title="<?= $source_title ?>">Link to Hansard source</a></li>
            </ul>
        </div>
    </div>

    <?php $previous_speech_time = $speech['htime']; ?>

  <?php } // end foreach ?>
</div>
