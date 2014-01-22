<?php
/*
 This is the main file allowing users to manage email alerts.
 It is based on the file /user/index.php.
 The alerts depend on the class ALERT which is established in /includes/easyparliament/alert.php

The submitted flag means we've submitted some form of search. Having pid or
keyword present means we've picked one of those results (or come straight from
e.g. MP page), and should try and add, asking for email if needed.

FUNCTIONS
check_input()	Validates the edited or added alert data and creates error messages.
add_alert()	Adds alert to database depending on success.
display_search_form()	Shows the new form to enter alert data.
set_criteria()	Sets search criteria from information in MP and Keyword fields.
*/

include_once "../../includes/easyparliament/init.php";
include_once INCLUDESPATH . "easyparliament/people.php";
include_once INCLUDESPATH . "easyparliament/member.php";
include_once INCLUDESPATH . "easyparliament/searchengine.php";
include_once INCLUDESPATH . '../../commonlib/phplib/auth.php';
include_once INCLUDESPATH . '../../commonlib/phplib/crosssell.php';

$this_page = "alert";
$extra = null;

$ALERT = new ALERT;
$token = get_http_var('t');
$alert = $ALERT->check_token($token);

$message = '';
if ($action = get_http_var('action')) {
    $success = true;
    if ($action == 'Confirm') {
        $success = $ALERT->confirm($token);
        if ($success) {
            $criteria = $ALERT->criteria_pretty(true);
            $message = "<p>Your alert has been confirmed. You will now
            receive email alerts for the following criteria:</p>
            <ul>$criteria</ul> <p>This is normally the day after, but could
            conceivably be later due to issues at our or parliament.uk's
            end.</p>";
        }
    } elseif ($action == 'Suspend') {
        $success = $ALERT->suspend($token);
        if ($success)
            $message = '<p><strong>That alert has been suspended.</strong> You will no longer receive this alert.</p>';
    } elseif ($action == 'Resume') {
        $success = $ALERT->resume($token);
        if ($success)
            $message = '<p><strong>That alert has been resumed.</strong> You
            will now receive email alerts on any day when there are entries in
            Hansard that match your criteria.</p>';
    } elseif ($action == 'Delete') {
        $success = $ALERT->delete($token);
        if ($success)
            $message = '<p><strong>That alert has been deleted.</strong> You will no longer receive this alert.</p>';
    }
    if (!$success)
        $message = "<p>The link you followed to reach this page appears to be
        incomplete.</p> <p>If you clicked a link in your alert email you may
        need to manually copy and paste the entire link to the 'Location' bar
        of the web browser and try again.</p> <p>If you still get this message,
        please do <a href='mailto:" . str_replace('@', '&#64;', CONTACTEMAIL) . "'>email us</a> and let us
        know, and we'll help out!</p>";
}

$details = array();
if ($THEUSER->loggedin()) {
    $details['email'] = $THEUSER->email();
    $details['email_verified'] = true;
} elseif ($alert) {
    $details['email'] = $alert['email'];
    $details['email_verified'] = true;
} else {
    $details["email"] = trim(get_http_var("email"));
    $details['email_verified'] = false;
}
$details['keyword'] = trim(get_http_var("keyword"));
$details['pid'] = trim(get_http_var("pid"));
$details['alertsearch'] = trim(get_http_var("alertsearch"));
$details['pc'] = get_http_var('pc');
$details['submitted'] = get_http_var('submitted') || $details['pid'] || $details['keyword'];

$errors = check_input($details);

// Do the search
if ($details['alertsearch']) {
    $details['members'] = search_member_db_lookup($details['alertsearch'], true);
    list ($details['constituencies'], $details['valid_postcode']) = search_constituencies_by_query($details['alertsearch']);
}

if (!sizeof($errors) && ($details['keyword'] || $details['pid'])) {
    $message = add_alert( $details );
    $details['keyword'] = '';
    $details['pid'] = '';
    $details['alertsearch'] = '';
    $details['pc'] = '';
}

$PAGE->page_start();
$PAGE->stripe_start();
if ($message) {
    $PAGE->informational($message);
}

$sidebar = null;
if ($details['email_verified']) {
    ob_start();
    if ($THEUSER->postcode()) {
        $current_mp = new MEMBER(array('postcode' => $THEUSER->postcode()));
        if (!$ALERT->fetch_by_mp($THEUSER->email(), $current_mp->person_id())) {
            $PAGE->block_start(array ('title'=>'Your current MP'));
?>
<form action="/alert/" method="post">
<input type="hidden" name="t" value="<?=htmlspecialchars(get_http_var('t'))?>">
<input type="hidden" name="pid" value="<?=$current_mp->person_id()?>">
You are not subscribed to an alert for your current MP,
<?=$current_mp->full_name() ?>.
<input type="submit" value="Subscribe">
</form>
<?
            $PAGE->block_end();
        }
    }
    $PAGE->block_start(array ('title'=>'Your current email alerts'));
    alerts_manage($details['email']);
    $PAGE->block_end();
    $sidebar = ob_get_clean();
}

$PAGE->block_start(array ('id'=>'alerts', 'title'=>'Request a TheyWorkForYou email alert'));
display_search_form($alert, $details, $errors);
$PAGE->block_end();

$end = array();
if ($sidebar) {
    $end[] = array('type' => 'html', 'content' => $sidebar);
}
$end[] = array('type' => 'include', 'content' => 'minisurvey');
$end[] = array('type' => 'include', 'content' => 'mysociety_news');
$end[] = array('type' => 'include', 'content' => 'search');
$PAGE->stripe_end($end);
$PAGE->page_end($extra); 

# ---

function check_input ($details) {
    global $SEARCHENGINE;

    $errors = array();

    // Check each of the things the user has input.
    // If there is a problem with any of them, set an entry in the $errors array.
    // This will then be used to (a) indicate there were errors and (b) display
    // error messages when we show the form again.

    // Check email address is valid and unique.
    if (!$details['email']) {
        $errors["email"] = "Please enter your email address";
    } elseif (!validate_email($details["email"])) {
        // validate_email() is in includes/utilities.php
        $errors["email"] = "Please enter a valid email address";
    } 

    if ($details['pid'] && !ctype_digit($details['pid'])) {
        $errors['pid'] = 'Invalid person ID passed';
    }

    $text = $details['alertsearch'];
    if (!$text) $text = $details['keyword'];

    if ($details['submitted'] && !$details['pid'] && !$text) {
        $errors['alertsearch'] = 'Please enter what you want to be alerted about';
    }

    if (strpos($text, '..')) {
        $errors['alertsearch'] = 'You probably don&rsquo;t want a date range as part of your criteria, as you won&rsquo;t be alerted to anything new!';
    }

    $se = new SEARCHENGINE($text);
    if (!$se->valid) {
        $errors['alertsearch'] = 'That search appears to be invalid - ' . $se->error . ' - please check and try again.';
    }

    if (strlen($text) > 255) {
        $errors['alertsearch'] = 'That search is too long for our database; please split it up into multiple smaller alerts.';
    }

    return $errors;
}

function add_alert ($details) {
    global $THEUSER, $ALERT, $extra;

	$external_auth = auth_verify_with_shared_secret($details['email'], OPTION_AUTH_SHARED_SECRET, get_http_var('sign'));
	if ($external_auth) {
		$site = get_http_var('site');
		$extra = 'from_' . $site . '=1';
		$confirm = false;
	} elseif ($details['email_verified']) {
		$confirm = false;
	} else {
		$confirm = true;
	}

	// If this goes well, the alert will be added to the database and a confirmation email 
	// will be sent to them.
	$success = $ALERT->add ( $details, $confirm );
	
	$advert = false;
	if ($success>0 && !$confirm) {
		if ($details['pid']) {
			$MEMBER = new MEMBER(array('person_id'=>$details['pid']));
			$criteria = $MEMBER->full_name();
			if ($details['keyword']) {
				$criteria .= ' mentions \'' . $details['keyword'] . '\'';
			} else {
				$criteria .= ' contributes';
			}
		} elseif ($details['keyword']) {
			$criteria = '\'' . $details['keyword'] . '\' is mentioned';
		}
		$message = array(
			'title' => 'Your alert has been added',
			'text' => 'You will now receive email alerts on any day when ' . $criteria . ' in parliament.'
		);
		$advert = true;
	} elseif ($success>0) {
		$message = array(
			'title' => "We're nearly done...",
			'text' => "You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address to receive the alert. Thanks."
		);
	} elseif ($success == -2) {
		// we need to make sure we know that the person attempting to sign up 
		// for the alert has that email address to stop people trying to work
		// out what alerts they are signed up to
		if ( $details['email_verified'] || ( $THEUSER->loggedin && $THEUSER->email() == $details['email'] ) ) {
			$message = array('title' => 'You already have this alert',
			'text' => 'You already appear to be subscribed to this email alert, so we have not signed you up to it again.'
			);
		} else {
			// don't throw an error message as that implies that they have already signed
			// up for the alert but instead pretend all is normal but send an email saying
			// that someone tried to sign them up for an existing alert
			$ALERT->send_already_signedup_email($details);
			$message = array('title' => "We're nearly done...",
				'text' => "You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address to receive the alert. Thanks."
			);
		}
		$advert = true;
	} else {
		$message = array ('title' => "This alert has not been accepted",
		'text' => "Sorry, we were unable to create this alert. Please <a href=\"mailto:" . str_replace('@', '&#64;', CONTACTEMAIL) . "\">let us know</a>. Thanks."
		);
	}
    return $message['text'];
}

/*  This function creates the form for displaying an alert, prompts the user for input and creates
    the alert when submitted.
*/

function display_search_form ( $alert, $details = array(), $errors = array() ) {
    global $this_page, $PAGE;

    $ACTIONURL = new URL($this_page);
    $ACTIONURL->reset();
    $form_start = '<form action="' . $ACTIONURL->generate() . '" method="post">
<input type="hidden" name="t" value="' . htmlspecialchars(get_http_var('t')) . '">
<input type="hidden" name="email" value="' . htmlspecialchars(get_http_var('email')) . '">';

    if (isset($details['members']) && $details['members']->rows() > 0) {
        echo '<ul class="hilites">';
        $q = $details['members'];
        $last_pid = null;
        for ($n=0; $n<$q->rows(); $n++) {
            if ($q->field($n, 'person_id') != $last_pid) {
                $last_pid = $q->field($n, 'person_id');
                echo '<li>';
                echo $form_start . '<input type="hidden" name="pid" value="' . $last_pid . '">';
                echo 'Things by ';
                $name = member_full_name($q->field($n, 'house'), $q->field($n, 'title'), $q->field($n, 'first_name'), $q->field($n, 'last_name'), $q->field($n, 'constituency') );
                if ($q->field($n, 'house') != 2) {
                    echo $name . ' (' . $q->field($n, 'constituency') . ') ';
                } else {
                    echo $name;
                }
                echo ' <input type="submit" value="Subscribe"></form>';
                echo "</li>\n";
            }
        }
        echo '</ul>';
    }

    if (isset($details['constituencies'])) {
        echo '<ul class="hilites">';
        foreach ($details['constituencies'] as $constituency) {
            $MEMBER = new MEMBER(array('constituency'=>$constituency, 'house' => 1));
            echo "<li>";
            echo $form_start . '<input type="hidden" name="pid" value="' . $MEMBER->person_id() . '">';
            if ($details['valid_postcode'])
                echo '<input type="hidden" name="pc" value="' . htmlspecialchars($details['alertsearch']) . '">';
            echo $MEMBER->full_name();
            echo ' (' . htmlspecialchars($constituency) . ')';
            echo ' <input type="submit" value="Subscribe"></form>';
            echo "</li>";
        }
        echo '</ul>';
    }

    if ($details['alertsearch']) {
        echo '<ul class="hilites"><li>';
        echo $form_start . '<input type="hidden" name="keyword" value="' . htmlspecialchars($details['alertsearch']) . '">';
        echo 'Mentions of [';
		$alertsearch = $details['alertsearch'];
        if (preg_match('#speaker:(\d+)#', $alertsearch, $m)) {
			$MEMBER = new MEMBER(array('person_id'=>$m[1]));
		    $alertsearch = str_replace("speaker:$m[1]", "speaker:" . $MEMBER->full_name(), $alertsearch);
        }
        echo htmlspecialchars($alertsearch) . '] ';
        echo ' <input type="submit" value="Subscribe"></form>';
        if (strstr($alertsearch, ',') > -1) {
            echo '<em class="error">You have used a comma in your search term &ndash; are you sure this is what you want?
You cannot sign up to multiple search terms using a comma &ndash; either use OR, or fill in this form multiple times.</em>';
        }
        echo "</li></ul>";
    }

    if ($details['pid']) {
        $MEMBER = new MEMBER(array('person_id'=>$details['pid']));
        echo '<ul class="hilites"><li>';
        echo "Signing up for things by " . $MEMBER->full_name();
        echo ' (' . htmlspecialchars($MEMBER->constituency()) . ')';
        echo "</li></ul>";
    }

    if ($details['keyword']) {
        echo '<ul class="hilites"><li>';
        echo 'Signing up for results from a search for [';
		$alertsearch = $details['keyword'];
        if (preg_match('#speaker:(\d+)#', $alertsearch, $m)) {
			$MEMBER = new MEMBER(array('person_id'=>$m[1]));
		    $alertsearch = str_replace("speaker:$m[1]", "speaker:" . $MEMBER->full_name(), $alertsearch);
        }
        echo htmlspecialchars($alertsearch) . ']';
        echo "</li></ul>";
    }

    if (!$details['pid'] && !$details['keyword']) {
?>

<p><label for="alertsearch">To sign up to an email alert, enter either your
<strong>postcode</strong>, the <strong>name</strong> of who you're interested
in, or the <strong>search term</strong> you wish to receive alerts
for.</label> To be alerted on an exact <strong>phrase</strong>, be sure to put it in quotes.
Also use quotes around a word to avoid stemming (where &lsquo;horse&rsquo; would
also match &lsquo;horses&rsquo;).

<?
    }

    echo '<form action="' . $ACTIONURL->generate() . '" method="post">
<input type="hidden" name="t" value="' . htmlspecialchars(get_http_var('t')) . '">
<input type="hidden" name="submitted" value="1">';

    if ((!$details['pid'] && !$details['keyword']) || isset($errors['alertsearch'])) {
        if (isset($errors["alertsearch"])) {
            $PAGE->error_message($errors["alertsearch"]);
        }
        $text = $details['alertsearch'];
        if (!$text) $text = $details['keyword'];
?>

<div class="row">
<input type="text" name="alertsearch" id="alertsearch" value="<?php if ($text) { echo htmlentities($text); } ?>" maxlength="255" size="30" style="font-size:150%">
</div>

<?php
    }

    if ($details['pid'])
        echo '<input type="hidden" name="pid" value="' . htmlspecialchars($details['pid']) . '">';
    if ($details['keyword'])
        echo '<input type="hidden" name="keyword" value="' . htmlspecialchars($details['keyword']) . '">';

    if (!$details['email_verified']) {
        if (isset($errors["email"]) && $details['submitted']) {
            $PAGE->error_message($errors["email"]);
        }
?>
        <div class="row">
            <label for="email">Your email address:</label>
            <input type="text" name="email" id="email" value="<?php if (isset($details["email"])) { echo htmlentities($details["email"]); } ?>" maxlength="255" size="30" class="form">
        </div>
<?php
    }
?>

    <div class="row">   
        <input type="submit" class="submit" value="<?=
            ($details['pid'] || $details['keyword']) ? 'Subscribe' : 'Search'
        ?>">
    </div>

    <div class="row">
<?php
    if (!$details['email_verified']) {
?>
        <p>If you <a href="/user/?pg=join">join</a> or <a href="/user/login/?ret=%2Falert%2F">sign in</a>, you won't need to confirm your email
        address for every alert you set.<br><br>
<?php
    }
    if (!$details['pid'] && !$details['keyword']) {
?>
        <p>Please note that you should only enter <strong>one term per alert</strong> &ndash; if
        you wish to receive alerts on more than one thing, or for more than
        one person, simply fill in this form as many times as you need, or use boolean OR.<br><br></p>
        <p>For example, if you wish to receive alerts whenever the words
        <i>horse</i> or <i>pony</i> are mentioned in Parliament, please fill in
        this form once with the word <i>horse</i> and then again with the word
        <i>pony</i> (or you can put <i>horse OR pony</i> with the OR in capitals
        as explained on the right). Do not put <i>horse, pony</i> as that will only
        sign you up for alerts where <strong>both</strong> horse and pony are mentioned.</p>
<?php
    }
?>
    </div>
<?php
    if (get_http_var('sign'))
        echo '<input type="hidden" name="sign" value="' . htmlspecialchars(get_http_var('sign')) . '">';
    if (get_http_var('site'))
        echo '<input type="hidden" name="site" value="' . htmlspecialchars(get_http_var('site')) . '">';
    echo '</form>';
}
