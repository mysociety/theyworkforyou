<?php
    global $hansardmajors;

    twfy_debug("TEMPLATE", "hansard_gid.php");
?>
<div class="debate-header">
    <div class="full-page__row">
        <div class="debate-header__content">
            <h1><?= $heading ?></h1>
            <p class="lead">
                <?= $intro ?> <?= $location ?><br>
                <?php if ($debate_time_human) { ?>at <?= $debate_time_human ?><?php } ?>
                on <a href="<?= $debate_day_link ?>"><?= $debate_day_human ?></a>.
            </p>
            <p class="cta">
              <?php if(isset($full_debate_url)) { ?>
                <a class="button subtle" href="<?= $full_debate_url ?>">Show full debate</a>
              <?php } ?>
                <a class="button alert" href="/alerts/?alertsearch=<?= urlencode($email_alert_text) ?>">Alert me about debates like this</a>
            </p>
        </div>
    </div>
</div>
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
            $body = preg_replace('#<citation id="uk\.org\.publicwhip/(.*?)/(.*?)">\[(.*?)\]</citation>#e',
                "'[<a href=\"/' . ('$1'=='spor'?'sp/?g':('$1'=='spwa'?'spwrans/?':'debates/?')) . 'id=$2' . '\">$3</a>]'",
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

        $body = str_replace(array('<br/>', '</p><p'), array('</p> <p>', '</p> <p'), $body); # NN4 font size bug
    }

    # TODO Do in the view, not in the template
    $source_url = '';
    if (isset($speech['source_url']) && $speech['source_url'] != '') {
        $source_url = $speech['source_url'];
        $source_title = '';
        $major = $data['info']['major'];
        if ($major==1 || $major==2 || (($major==3 || $major==4) && isset($speech['speaker']['house'])) || $major==101 || $major==6) {
            $source_title = 'Citation: ';
            if ($major==1 || $major==2) {
                $source_title .= 'HC';
            } elseif ($major==3 || $major==4) {
                if ($speech['speaker']['house']==1) {
                    $source_title .= 'HC';
                } else {
                    $source_title .= 'HL';
                }
            } elseif ($major==6) {
                $source_title .= $section['title'];
            } else {
                $source_title .= 'HL';
            }
            $source_title .= ' Deb, ' . format_date($data['info']['date'], LONGDATEFORMAT) . ', c' . $speech['colnum'];
            if ($major==2) {
                $source_title .= 'WH';
            } elseif ($major==3) {
                $source_title .= 'W';
            } elseif ($major==4) {
                $source_title .= 'WS';
            }
        }
        $source_text = "Hansard source";
        if ($hansardmajors[$data['info']['major']]['location']=='Scotland'){
            $source_text = 'Official Report source';
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
                    $speaker_position = $speaker['office'][0]['pretty'];
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
            </h2>
          <?php } ?>
            <div class="debate-speech__content"><?=$body ?></div>
            <ul class="debate-speech__meta">
              <?php if (!isset($previous_speech_time) || $previous_speech_time != $speech['htime']) { ?>
                <li class="time">
                    <a href="<?= $speech['listurl'] ?>">
                        <?= format_time($speech['htime'], 'g:i a') ?>,
                        <?= format_date($speech['hdate'], 'jS F Y') ?>
                    </a>
                </li>
              <?php } ?>
                <li class="link-to-speech"><a href="<?= $speech['listurl'] ?>">Link to this speech</a></li>
<?php
                if ($source_url) {
?>
                <li class="link-to-hansard"><a href="<?=$source_url ?>"><?=$source_text ?></a>
                <?php if ($source_title) { ?> (<?=$source_title ?>)<?php } ?></li>
<?php
                }
?>
            </ul>
        </div>
    </div>

    <?php $previous_speech_time = $speech['htime']; ?>

  <?php } // end foreach ?>
</div>

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
