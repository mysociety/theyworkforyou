<?php
    global $hansardmajors;

    twfy_debug("TEMPLATE", "hansard_gid.php");
?>
<div class="debate-header">
    <div class="full-page__row">
        <div class="debate-header__content">
            <h1><?= $heading ?></h1>
            <p class="lead">
                <?= $intro ?> <?= $location ?>
                <?php if ($debate_time_human) { ?>at <?= $debate_time_human ?><?php } ?>
                on <a href="<?= $debate_day_link ?>"><?= $debate_day_human ?></a>.
            </p>
            <p class="cta">
                <a class="button alert" href="/alerts/?alertsearch=<?= urlencode($email_alert_text) ?>">Alert me about debates like this</a>
            </p>
        </div>
    </div>
    <nav class="debate-navigation" role="navigation">
        <div class="full-page__row">
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
    </nav>
</div>
<?php if ($hansardmajors[$data['info']['major']]['location'] == 'Scotland') { ?>
<div class="full-page">
    <div class="debate-speech__notice">
        <div class="full-page__row">
            Due to changes made to the official Scottish Parliament, our parser that used to fetch their web pages and convert them into more structured information has stopped working. We're afraid we cannot give a timescale as to when we will be able to cover the Scottish Parliament again. Sorry for any inconvenience caused.
        </div>
    </div>
</div>

<?php } ?>

<div class="full-page">

  <?php foreach($data['rows'] as $speech) { ?>

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
            $begtomove = preg_replace('#(That this House |; )(\w+)#', '\1<br><strong>\2</strong>', $rrr);
            $body = str_replace($rrr, $begtomove, $body);
        }

        # New written statement/answer links can be internal from 2015-02-06
        $body = preg_replace('#http://www.parliament.uk/business/publications/written-questions-answers-statements/written-statement/(?:Commons|Lords)/(?!2015-01)(?!2015-02-0[1-5])(\d\d\d\d-\d\d-\d\d)/(H[CL]WS\d+)/#', '/wms/?id=\1.\2.h', $body);
        # But also can have empty paragraphs in them
        $body = preg_replace('#<p>\s*</p>#', '', $body);

        $body = str_replace(array('<br/>', '</p><p'), array('</p> <p>', '</p> <p'), $body); # NN4 font size bug
    }

    # TODO Do in the view, not in the template

    $source = null;

    if (isset($speech['source_url']) && $speech['source_url'] != '') {
        $source = array(
            'url' => $speech['source_url']
        );
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
        } else {
            $source['title'] = null;
        }

        if ($hansardmajors[$data['info']['major']]['location']=='Scotland'){
            $source['text'] = 'Official Report source';
        } else {
            $source['text'] = 'Hansard source';
        }
    }

    ?>

    <div class="debate-speech" id="g<?= gid_to_anchor($speech['gid']) ?>">
        <div class="full-page__row">
            <a name="g<?= gid_to_anchor($speech['gid']) ?>"></a>

          <?php if(isset($speech['speaker']) && count($speech['speaker']) > 0) { ?>
            <h2 class="debate-speech__speaker">
                <?php

                $speaker = $speech['speaker'];
                $speaker_name = ucfirst(member_full_name(
                    $speaker['house'],
                    $speaker['title'],
                    $speaker['first_name'],
                    $speaker['last_name'],
                    $speaker['constituency']
                ));

                list($image_url, $size) = find_rep_image(
                    $speaker['person_id'],
                    true,
                    $data['info']['major'] == 101 ? 'lord' : 'general'
                );

                if (isset($speaker['office'])) {
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
                <?php if (!isset($previous_speech_time) || $previous_speech_time != $speech['htime']) { ?>
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
            <div class="debate-speech__content"><?=$body ?></div>

            <?php if ($speech['voting_data']) { ?>

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

            // Video
            if ($data['info']['major'] == 1 && !$individual_item) { # Commons debates only
                if ($speech['video_status']&4) { ?>
                    <a href="<?= $speech['commentsurl'] ?>" class="watch debate-speech__meta__link" onclick="return moveVideo(\'debate/'<?= $speech['gid'] ?>\');">Watch this</a>
                <?php
                } elseif (!$speech['video'] && $speech['video_status']&1 && !($speech['video_status']&8)) {
                    $gid_type = $data['info']['major'] == 1 ? 'debate' : 'lords'; ?>
                    <a href="/video/?from=debate&amp;gid=<?= $gid_type ?>/<?= $speech['gid'] ?>" class="timestamp debate-speech__meta__link">Video match this</a>
                <?php
                }
            }

            if (isset($speech['video']) && $speech['video']) {
                ?>
                <div class="debate__video-wrapper">
                <?php
                echo $speech['video'];
                ?>
                </div>
                <?php
            }

            # XXX
            if ($hansardmajors[$speech['major']]['type'] == 'debate' && $individual_item) {
                if ($speech['htype'] == '12') {
                    $thing = 'speech';
                } elseif ($speech['htype'] == '13') {
                    $thing = 'item';
                } else {
                    $thing = 'item';
                }
            ?>
            <p class="speech-link-in-context"><a href="<?= $speech['listurl'] ?>" class="permalink link debate-speech__meta__link">See this <?= $thing ?> in context</a></p>
            <?php
            } # End in context link

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

            ?>
            <ul class="debate-speech__meta debate-speech__links">
                <?php if (!$individual_item) { # XXX ?>
                <li class="link-to-speech">
                    <span class="link-to-speech__label">Link to this speech</span>

                    <a href="<?= $speech['listurl'] ?>" class="link debate-speech__meta__link">In context</a>

                    <a href="<?= $speech['commentsurl'] ?>" class="link debate-speech__meta__link">Individually</a>
                </li>
                <?php } ?>
<?php
                if ($source != null) {
?>
                <li class="link-to-hansard "><a href="<?=$source['url'] ?>" class="debate-speech__meta__link"><?=$source['text'] ?></a>
                <?php if ($source['title']) { ?><span> (<?=$source['title'] ?>)</span><?php } ?></li>
<?php
                }
                if (isset($speech['mentions'])) {
                    echo $speech['mentions'];
                }
?>
            </ul>
        </div>
    </div>

    <?php $previous_speech_time = $speech['htime']; ?>

  <?php } // end foreach
        if (isset($data['subrows'])) {
        print '<div class="subrows"><div class="full-page__row"><ul>';
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
        print '</ul></div></div>';
    }
    if ($individual_item) { ?>
        <div class="debate-comments">
            <div class="full-page__row">
            <?php
                # XXX
                global $PAGE;
                $comments['object']->display('ep', $comments['args']);
                $PAGE->comment_form($comments['commentdata']);
                # XXX COMMENT SIDEBAR SHOULD GO HERE IF LOGGED IN
            ?>
            </div>
        </div>
        <?php
    }

?>

</div>
<nav class="debate-navigation debate-navigation--footer" role="navigation">
        <div class="full-page__row">
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
    </nav>
<?php

/*

Structure of the $data array.

(Notes for the diagram below...)
The 'info' section is metadata about the results set as a whole.

'rows' is an array of items to display, each of which has a set of Hansard object data and more. The item could be a section heading, subsection, speech, written question, procedural, etc, etc.


In the diagram below, 'HansardObjectData' indicates a standard set of key/value
pairs along the lines of:
    'epobject_id'    => '31502',
    'gid'            => '2003-12-31.475.3',
    'hdate'            => '2003-12-31',
    'htype'            => '12',
    'body'            => 'A lot of text here...',
    'listurl'        => '/debates/?id=2003-12-31.475.0#g2003-12-31.475.3',
    'commentsurl'    => '/debates/?id=2003-12-31.475.3',
    'speaker_id'    => '931',
    'speaker'        => array (
        'member_id'        => '931',
        'first_name'    => 'Peter',
        'last_name'        => 'Hain',
        'constituency'    => 'Neath',
        'party'            => 'Lab',
        'url'            => '/member/?id=931'
    ),
    'totalcomments'    => 5,
    'comment'        => array (
        'user_id'        => '45',
        'body'            => 'Comment text here...',
        'posted'        => '2003-12-31 23:00:00',
        'username'        => 'William Thornton',
    ),
    'votes'    => array (
        'user'    => array (
            'yes'    => '21',
            'no'    => '3'
        ),
        'anon'    => array (
            'yes'    => '132',
            'no'    => '30'
        )
    ),
    'trackback'        => array (
        'itemurl'        => 'http://www.easyparliament.com/debates/?id=2003-12-31.475.3',
        'pingurl'        => 'http://www.easyparliament.com/trackback?g=debate_2003-02-28.475.3',
        'title'            => 'Title of this item or page',
        'date'            => '2003-12-31T23:00:00+00:00'
    )
    etc.

Note: There are two URLs.
    'listurl' is a link to the item in context, in the list view.
    'commentsurl' is the page where we can see this item and all its comments.

Note: The 'trackback' array won't always be there - only if we think we're going to
    be using it for Auto Discovery on this page.

Note: Speaker's only there if there is a speaker for this item.


$data = array (

    'info' => array (
        'date'    => '2003-12-31',
        'text'    => 'A brief bit of text for a title...',
        'searchwords' => array ('fox', 'hunting')
    ),

    'rows' => array (
        0 => array ( HansardObjectData ),
        1 => array ( HansardObjectData ), etc...
    )
);


*/
