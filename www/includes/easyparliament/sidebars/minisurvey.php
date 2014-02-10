<?php
/*
 * survey/index.php:
 * Ask and store questionnaire for research.
 *
 * Copyright (c) 2009 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: index.php,v 1.4 2010-01-20 10:48:58 matthew Exp $
 *
 */

include_once INCLUDESPATH . "easyparliament/init.php";
require_once INCLUDESPATH . "../../commonlib/phplib/random.php";
require_once INCLUDESPATH . "../../commonlib/phplib/auth.php";

// increment this each time you change the question so
// the cookie magic works
$current_question = 2;
$always_ask = 1;
$survey_site = "twfy-mini-$current_question";
$show_survey_qn = 0;
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$has_answered_question = get_http_var('answered_survey');
$hide_question = get_http_var('hide_survey');

// we never want to display this on the front page or any
// other survey page we might have
if (in_array($this_page, array('survey', 'overview'))) {
    return;
}

if ($hide_question) {
    $always_ask = 0;
    $show_survey_qn = $current_question;
    setcookie('survey', $current_question, time()+60*60*24*365, '/');
} elseif ($has_answered_question == $current_question && !$always_ask) {
    $show_survey_qn = $current_question;
    setcookie('survey', $current_question, time()+60*60*24*365, '/');
} elseif (isset($_COOKIE['survey'])) {
    $show_survey_qn = $_COOKIE['survey'];
}

$survey_id = 'minisurvey';
if ($always_ask) {
    $survey_id = 'minisurvey-show';
}

if ($show_survey_qn < $current_question && !$has_answered_question) {
    $page_url = '';
    $hide_url = '';
    if ( in_array( $this_page, array('mp', 'peer', 'msp', 'mla', 'royal') ) ) {
        global $MEMBER;
        $page_url = $MEMBER->url() . "?answered_survey=$current_question";
        $hide_url = $MEMBER->url() . "?hide_survey=$current_question";
    } else {
        $URL = new URL($this_page);
        $URL->insert(array('answered_survey' => $current_question ));
        $page_url = 'http://' . DOMAIN . $URL->generate();
        $URL = new URL($this_page);
        $URL->insert(array('hide_survey' => $current_question ));
        $hide_url = 'http://' . DOMAIN . $URL->generate();
    }

    $user_code = bin2hex(urandom_bytes(16));
    $auth_signature = auth_sign_with_shared_secret($user_code, OPTION_SURVEY_SECRET);

    $this->block_start(array('id'=>$survey_id, 'title'=>'Mini survey. <small><a href="/help/#survey">What is this about?</a></small>'));
?>

<form class="minisurvey" method="post" action="<?=OPTION_SURVEY_URL?>">
<input type="hidden" name="sourceidentifier" value="<?=$survey_site ?>">
    <input type="hidden" name="datetime" value="<?=time() ?>">
    <input type="hidden" name="subgroup" value="0">

    <input type="hidden" name="user_code" value="<?=$user_code ?>">
    <input type="hidden" name="auth_signature" value="<?=$auth_signature ?>">

    <input type="hidden" name="came_from" value="<?=$page_url ?>">
    <input type="hidden" name="return_url" value="<?=$page_url ?>">
    <input type="hidden" name="question_no" value="<?=$current_question ?>">

    <p>
    Did you find what you were looking for on this page?
    </p>
    <ul>
        <li><label><input type="radio" name="find_on_page" value="1"  id="id_find_on_page_yes" onclick="$('#minisurvey-further-details').hide()">Yes</label></li>
        <li><label><input type="radio" name="find_on_page" value="0"  id="id_find_on_page_no" onclick="$('#minisurvey-further-details').show()">No</label></li>
    </ul>

    <div id="minisurvey-further-details" hidden>

        <p>
            <label for="minisurvey-looking-for">What were you looking for?</label>
            <input id="minisurvey-looking-for" name="looking_for">
        </p>

    </div>

    <p>
    <input type="submit" value="Submit answer"> <small><a href="<?=$hide_url ?>">Hide this</a></small>
    </p>

</form>

<?php
    $this->block_end();
} elseif ($has_answered_question) {
    $this->block_start(array('id'=>'survey', 'title'=>"Mini survey"));
?>
    Thanks for answering.
<?php
    $this->block_end();
}
