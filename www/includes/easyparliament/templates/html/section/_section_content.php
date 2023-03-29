<?php

foreach($data['rows'] as $speech) { ?>

    <?php

    if ($speech['htype'] == 10 || $speech['htype'] == 11) {
        continue; // This is a heading, not a speech. Ignore it.
    }

    # TODO Do in the view, not in the template
    $body = $speech['body'];
    if ($speech['htype'] == 12) {
        if ($hansardmajors[$data['info']['major']]['location'] == 'Scotland') {
            $body = preg_replace('# (S\d[O0WF]-\d+)[, ]#', ' <a href="/spwrans/?spid=$1">$1</a> ', $body);
            $body = preg_replace_callback('#<citation id="uk\.org\.publicwhip/(.*?)/(.*?)">\[(.*?)\]</citation>#', function($matches) {
                   if ($matches[1] == 'spor') {
                       $href_segment = 'sp/?g';
                   } elseif ($matches[1] == 'spwa') {
                        $href_segment = 'spwrans/?';
                    } else {
                        $href_segment = 'debates/?';
                    }
                    return '[<a href="' . $href_segment . 'id=' . $matches[2] . '\">' . $matches[3] . '</a>]';
                },
                $body);
            $body = str_replace('href="../../../', 'href="http://www.scottish.parliament.uk/', $body);
        }

        if (preg_match('#\[Official Report, (.*?)[,;] (.*?) (\d+MC)\.\]#', $body)) {
            # Why, you may ask, would someone have to check whether a
            # regular expression matches before replacing that regular
            # expression with some other text? And all I can tell you,
            # future person reading this, is that otherwise occasionally
            # this replacement removes the entire contents of $body, even
            # though it has no matches (it does have e.g. "[Official
            # Report,", but not at the start of the string). Thanks, PHP.
            $body = preg_replace('#\[Official Report, (.*?)[,;] (.*?) (\d+MC)\.\]#', '<big>[This section has been corrected on $1, column $3 &mdash; read correction]</big>', $body);
        }
        $body = preg_replace('#(<p[^>]*class="[^"]*?)("[^>]*)pwmotiontext="moved"#', '$1 moved$2', $body);
        $body = str_replace('pwmotiontext="moved"', 'class="moved"', $body);
        $body = str_replace('<a href="h', '<a rel="nofollow" href="h', $body); # As even sites in Hansard lapse and become spam-sites

        preg_match_all('#<p[^>]* pwmotiontext="yes">.*?</p>#s', $body, $m);
        foreach ($m as $rrr) {
            $begtomove = preg_replace('#(That this House |[^0-9]; )(\w+)#', '\1<br><strong>\2</strong>', $rrr);
            $body = str_replace($rrr, $begtomove, $body);
        }

        # New written statement/answer links can be internal from 2015-02-06
        $body = preg_replace('#http://www.parliament.uk/business/publications/written-questions-answers-statements/written-statement/(?:Commons|Lords)/(?!2015-01)(?!2015-02-0[1-5])(\d\d\d\d-\d\d-\d\d)/(H[CL]WS\d+)/#', '/wms/?id=\1.\2.h', $body);
        # But also can have empty paragraphs in them
        $body = preg_replace('#<p>\s*</p>#', '', $body);

        # Assume a paragraph starting with a lowercase character should be run on
        $body = preg_replace('#(?<!:|\]|&\#8221;,)</p>\s*<p[^>]*>(?=[a-z])(?![ivx]+\.)#', ' ', $body);

        $body = str_replace(array('<br/>', '</p><p'), array('</p> <p>', '</p> <p'), $body); # NN4 font size bug
    }

    # TODO Do in the view, not in the template

    $source = array();

    $major = $data['info']['major'];
    if ($major==1 || $major==2 || (($major==3 || $major==4) && isset($speech['speaker']['house'])) || $major==101 || $major==6) {
        $source['title'] = 'Citation: ';
        if ($major==1 || $major==2) {
            $source['title'] .= 'HC';
        } elseif ($major==3 || $major==4) {
            if ($speech['speaker']['house']==1) {
                $source['title'] .= 'HC';
            } else {
                $source['title'] .= 'HL';
            }
        } elseif ($major==6) {
            $source['title'] .= $data['section_title'];
        } else {
            $source['title'] .= 'HL';
        }
        $source['title'] .= ' Deb, ' . format_date($data['info']['date'], LONGDATEFORMAT) . ', c' . $speech['colnum'];
        if ($major==2) {
            $source['title'] .= 'WH';
        } elseif ($major==3) {
            $source['title'] .= 'W';
        } elseif ($major==4) {
            $source['title'] .= 'WS';
        }
    }

    if (isset($speech['source_url']) && $speech['source_url'] != '') {
        $source['url'] = $speech['source_url'];
        if ($hansardmajors[$major]['location'] == 'Scotland') {
            $source['text'] = 'Official Report source';
        } else {
            $source['text'] = 'Hansard source';
        }
    }

    ?>

    <div class="debate-speech" id="g<?= gid_to_anchor($speech['gid']) ?>">
        <div class="full-page__row">
            <div class="full-page__unit">

            <a name="g<?= gid_to_anchor($speech['gid']) ?>"></a>


            <?php if ($speech['htype'] == 14 && $speech['division']) { ?>
            <div class="debate-speech__division">
            <?php } elseif ($speech['major'] == 1 && $speech['minor'] == 2) { ?>
            <div class="debate-speech__speaker-and-content debate-speech__speaker-and-content--intervention">
            <?php } else { ?>
            <div class="debate-speech__speaker-and-content">
            <?php } ?>

          <?php if(isset($speech['speaker']) && count($speech['speaker']) > 0) { ?>
            <h2 class="debate-speech__speaker">
                <?php

                $speaker = $speech['speaker'];
                $speaker_name = ucfirst($speaker['name']);

                list($image_url, $size) = MySociety\TheyWorkForYou\Utility\Member::findMemberImage(
                    $speaker['person_id'],
                    true,
                    $data['info']['major'] == 101 ? 'lord' : 'general'
                );

                if (count($speaker['office'])) {
                    $desc = array();
                    foreach ($speaker['office'] as $off) {
                        $desc[] = $off['pretty'];
                    }
                    $speaker_position = join(', ', $desc);
                } else {
                    $speaker_position = _htmlentities($speaker['party']);
                    if ($speaker['house'] == 1 &&
                        $speaker['party'] != 'Speaker' &&
                        $speaker['party'] != 'Deputy Speaker' &&
                        $speaker['constituency']
                    ) {
                        $speaker_position .= ', ' . $speaker['constituency'];
                    }
                }

                ?>
                <a href="<?= $speech['speaker']['url'] ?>">
                    <img src="<?= $image_url ?>" alt="Photo of <?= $speaker_name ?>">
                    <strong class="debate-speech__speaker__name"><?= $speaker_name ?></strong>
                    <small class="debate-speech__speaker__position"><?= $speaker_position ?></small>
                </a>
                <?php if ($previous_speech_time != $speech['htime']) { ?>
                    <a href="<?= $speech['listurl'] ?>" class="debate-speech__meta__link time">
                      <?php if ($speech['htime']) { ?>
                        <?= format_time($speech['htime'], 'g:i a') ?>,
                      <?php } ?>
                        <?= format_date($speech['hdate'], 'jS F Y') ?>
                    </a>
              <?php } ?>

              <?php # XXX
                if ($data['info']['major'] == 8 && preg_match('#\d{4}-\d\d-\d\d\.(.*?)\.q#', $speech['gid'], $m)) {
                    ?><p class="debate-speech__question_id"><small>
                    <?= "Question $m[1]" ?>
                    </small></p>
              <?php } ?>
            </h2>


          <?php } ?>
            <?php if ($speech['htype'] == 14 && $speech['division']) {
                $division = $speech['division'];
                ?>
                <h2 class="debate-speech__division__header">
                    <img src="/images/bell.png">
                    <small class="debate-speech__division__number">Division number <?= $division['number'] ?></small>
                    <strong class="debate-speech__division__title"><?= $division['division_title'] ?></strong>
                </h2>

                <?php if ($division['has_description']) { ?>
                <div class="debate-speech__division__details">
                    <span class="policy-vote__text">
                        <?php include( dirname(__FILE__) . '/../divisions/_vote_description.php'); ?>
                    </span><br>
                </div>
                <?php } ?>

              <?php if (isset($speech['mp_vote'])) {
                $mp_vote = array( 'vote' => $speech['mp_vote']['vote'] );
                if ( isset($speech['before_mp']) ) {
                    $before_mp = $speech['before_mp'];
                }
                if ( isset($speech['after_mp']) ) {
                    $after_mp = $speech['after_mp'];
                }
                include dirname(__FILE__) . '/../divisions/_your_mp.php';
              } ?>
                <div class="debate-speech__division__details">
                  <?php include dirname(__FILE__) . '/../divisions/_votes.php'; ?>
                </div>
            <?php } else { ?>
            <div class="debate-speech__content"><?=$body ?></div>
            <?php } ?>

            <?php if ( $section ) {
                if ($speech['voting_data']) { ?>

                <div class="debate-speech__question-answered">
                    <div class="debate-speech__question-answered-content">
                        <h3>Does this answer the above question?</h3>
                        <p class="debate-speech__question-answered-result">
                            <a rel="nofollow" class="button" href="<?= $speech['voting_data']['yesvoteurl'] ?>" title="Rate this as answering the question">Yes</a><span class="question-answered-result__vote-text"><?= $speech['voting_data']['yesvotes'] ?> <?= $speech['voting_data']['yesplural'] ?> so</span>
                        </p>

                        <p class="debate-speech__question-answered-result">
                            <a rel="nofollow" class="button" href="<?= $speech['voting_data']['novoteurl'] ?>" title="Rate this as NOT answering the question">No</a><span class="question-answered-result__vote-text"><?= $speech['voting_data']['novotes'] ?> <?= $speech['voting_data']['noplural'] ?> not</span>
                        </p>

                        <p class="subtle">
                            Would you like to <strong>ask a question like this yourself</strong>? Use our <a href="http://www.whatdotheyknow.com">Freedom of Information site</a>.
                        </p>
                    </div>
                </div>

                <?php
                } # End of voting HTML

                if (isset($speech['commentteaser'])) { ?>
                <div class="comment-teaser">
                    <div class="comment-teaser__avatar">
                        <span class="initial"><?= substr($speech['commentteaser']['username'], 0, 1); ?></span>
                    </div>
                    <blockquote><p><?= $speech['commentteaser']['body'] ?></p><cite>Submitted by <?= $speech['commentteaser']['username'] ?></cite>

                    <a class="morecomments" href="<?= $speech['commentteaser']['commentsurl'] ?>#c<?= $speech['commentteaser']['comment_id'] ?>" title="See any annotations posted about this"><?= $speech['commentteaser']['linktext'] ?></a>
                    </blockquote>

                 </div>
                <?php
                }
            } ?>
            </div>

            <ul class="debate-speech__meta debate-speech__links">
                <?php
                    if ($speech['htype'] == '12') {
                        $thing = 'speech';
                    } elseif ($speech['htype'] == '14') {
                        $thing = 'vote';
                    } elseif ($speech['htype'] == '13') {
                        $thing = 'item';
                    } else {
                        $thing = 'item';
                    }
                ?>
                <?php if ($section && $hansardmajors[$speech['major']]['type'] == 'debate' && $individual_item) { ?>
                <li class="link-to-speech">
                    <a href="<?= $speech['listurl'] ?>" class="link debate-speech__meta__link">See this <?= $thing ?> in context</a>
                </li>
                <?php
                }
                if (!$section || !$individual_item) { ?>
                <li class="link-to-speech">
                    <span class="link-to-speech__label">Link to this <?= $thing ?></span>
                    <a href="<?= $speech['listurl'] ?>" class="link debate-speech__meta__link">In context</a>
                    <a href="<?= $speech['commentsurl'] ?>" class="link debate-speech__meta__link">Individually</a>
                </li>
                <?php
                }
                if ($speech['socialteaser'] && $speech['socialurl']) {
                    $twitter_href = sprintf(
                        'https://twitter.com/share?url=%s&text=%s&amp;related=%s',
                        urlencode($speech['socialurl']),
                        urlencode($speech['socialteaser']),
                        urlencode('theyworkforyou,mysociety')
                    );
                    $facebook_href = sprintf(
                        'https://www.facebook.com/dialog/share?app_id=%s&display=popup&href=%s&quote=%s',
                        urlencode(FACEBOOK_APP_ID),
                        urlencode($speech['socialurl']),
                        urlencode($speech['socialteaser'])
                    ); ?>
                <li class="link-to-speech">
                    <a href="<?=htmlspecialchars($twitter_href)?>" class="twitter debate-speech__meta__link js-twitter-share" target="_blank">Tweet</a>
                    <a href="<?=htmlspecialchars($facebook_href)?>" data-url="<?=htmlspecialchars($speech['socialurl'])?>" data-text="<?=htmlspecialchars($speech['socialteaser'])?>" class="facebook debate-speech__meta__link js-facebook-share">Share</a>
                </li>
                <?php
                }
                if ($source) { ?>
                <li class="link-to-hansard">
                  <?php if (isset($source['url'])) { ?>
                    <a href="<?=$source['url'] ?>" class="debate-speech__meta__link"><?=$source['text'] ?></a>
                  <?php } ?>
                    <?php if (isset($source['title'])) { ?><span> (<?=$source['title'] ?>)</span><?php } ?>
                </li>
                <?php
                }
                if (isset($speech['mentions'])) {
                    echo $speech['mentions'];
                } ?>

            </ul>

        </div>

        </div>
    </div>

    <?php $previous_speech_time = $speech['htime']; ?>

<?php

} // end foreach

    if (isset($data['subrows'])) {
        print '<div class="subrows"><div class="full-page__row"><div class="full-page__unit"><ul>';
        foreach ($data['subrows'] as $row) {
            print '<li class="subrows__list-item">';
            if (isset($row['contentcount']) && $row['contentcount'] > 0) {
                $has_content = true;
            } elseif ($row['htype'] == '11' && $hansardmajors[$row['major']]['type'] == 'other') {
                $has_content = true;
            } else {
                $has_content = false;
            }
            if ($has_content) {
                print '<a href="' . $row['listurl'] . '">' . $row['body'] . '</a> ';
                // For the "x speeches, x comments" text.
                $moreinfo = array();
                if ($hansardmajors[$row['major']]['type'] != 'other') {
                    // All wrans have 2 speeches, so no need for this.
                    // All WMS have 1 speech
                    $plural = $row['contentcount'] == 1 ? 'speech' : 'speeches';
                    $moreinfo[] = $row['contentcount'] . " $plural";
                }
                if ($row['totalcomments'] > 0) {
                    $plural = $row['totalcomments'] == 1 ? 'annotation' : 'annotations';
                    $moreinfo[] = $row['totalcomments'] . " $plural";
                }
                if (count($moreinfo) > 0) {
                    print "<small>(" . implode (', ', $moreinfo) . ") </small>";
                }
            } else {
                // Nothing in this item, so no link.
                print $row['body'];
            }
            if (isset($row['excerpt'])) {
                print "<p class=\"subrows__excerpt\">" . trim_characters($row['excerpt'], 0, 200) . "</p>";
            }
        }
        print '</ul></div></div></div>';
    }

    if ($section && $individual_item) { ?>
        <div class="debate-comments">
            <div class="full-page__row">
                <div class="full-page__unit">
                <?php
                    # XXX
                    global $PAGE;
                    $comments['object']->display('ep', $comments['args']);
                    # XXX COMMENT SIDEBAR SHOULD GO HERE IF LOGGED IN
                ?>
                </div>
            </div>
        </div>
<?php
    }
