<?php
/*
 This is the main file allowing users to manage email alerts.
 It is based on the file /user/index.php.
 The alerts depend on the class ALERT which is established in /includes/easyparliament/alert.php

submitted=1 means we've submitted some form of search.
only=1 means we've picked one of those results (or come straight from MP or
search results page), and should try and add, asking for email if needed.

FUNCTIONS
check_input()	Validates the edited or added alert data and creates error messages.
add_alert()	Adds alert to database depending on success.
display_search_form()	Shows the new form to enter alert data.
set_criteria()	Sets search criteria from information in MP and Keyword fields.
*/

include_once "../../includes/easyparliament/init.php";
include_once "../../includes/easyparliament/people.php";
include_once "../../includes/easyparliament/member.php";
include_once "../../includes/easyparliament/searchengine.php";
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
        please do <a href='mailto:" . CONTACTEMAIL . "'>email us</a> and let us
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
$details['add'] = get_http_var('only');

$errors = check_input($details);

// Do the search
if ($details['alertsearch']) {
    $details['members'] = search_member_db_lookup($details['alertsearch'], true);
    list ($details['constituencies'], $validpostcode) = search_constituencies_by_query($details['alertsearch']);
}

if (!sizeof($errors) && $details['add'] && ($details['keyword'] || $details['pid'])) {
    $message = add_alert( $details );
    $details['keyword'] = '';
    $details['pid'] = '';
    $details['alertsearch'] = '';
    $details['pc'] = '';
    $details['add'] = '';
}

$PAGE->page_start();
$PAGE->stripe_start();
if ($message) {
    $PAGE->informational($message);
}

$sidebar = null;
if ($details['email_verified']) {
    ob_start();
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
$end[] = array('type' => 'include', 'content' => 'mysociety_news');
$PAGE->stripe_end($end);
$PAGE->page_end($extra); 

# ---

function check_input ($details) {
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
	
	if ($details['pid'] && !ctype_digit($details['pid']))
		$errors['pid'] = 'Invalid person ID passed';

	if ((get_http_var('submitted') || $details['add']) && !$details['pid'] && !$details['alertsearch'] && !$details['keyword'])
		$errors['alertsearch'] = 'Please enter what you want to be alerted about';

	if (strpos($details['alertsearch'], '..') || strpos($details['keyword'], '..')) {
		$errors['alertsearch'] = 'You probably don&rsquo;t want a date range as part of your criteria, as you won&rsquo;t be alerted to anything new!';
	}

	return $errors;
}

function add_alert ($details) {
    global $ALERT, $extra;

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
		$message = array('title' => 'You already have this alert',
		'text' => 'You already appear to be subscribed to this email alert, so we have not signed you up to it again.'
		);
		$advert = true;
	} else {
		$message = array ('title' => "This alert has not been accepted",
		'text' => "Sorry, we were unable to create this alert. Please <a href=\"mailto:". CONTACTEMAIL . "\">let us know</a>. Thanks."
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
<input type="hidden" name="only" value="1">
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
                $name = member_full_name($q->field($n, 'house'), $q->field($n, 'title'), $q->field($n, 'first_name'), $q->field($n, 'last_name'), $q->field($n, 'constituency') );
                if ($q->field($n, 'house') == 1) {
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
            if ($validpostcode)
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
        echo 'Mentions of [' . htmlspecialchars($details['alertsearch']) . '] ';
        echo ' <input type="submit" value="Subscribe"></form>';
        echo "</li></ul>";
    }

    if ($details['pid']) {
        $MEMBER = new MEMBER(array('person_id'=>$details['pid']));
        echo '<ul class="hilites"><li>';
        echo "Signing up for " . $MEMBER->full_name();
        echo ' (' . htmlspecialchars($MEMBER->constituency()) . ')';
        echo "</li></ul>";
    }

    if ($details['keyword']) {
        echo '<ul class="hilites"><li>';
        echo 'Signing up for results from a search for [' . htmlspecialchars($details['keyword']) . ']';
        echo "</li></ul>";
    }

    if (!$details['add']) {
?>

<p><label for="alertsearch">To sign up to an email alert, enter your <strong>postcode</strong>, the
<strong>name</strong> of who you're interested in, or a <strong>word or
phrase</strong> you wish to receive alerts for:</label>

<?
    }

    echo '<form action="' . $ACTIONURL->generate() . '" method="post">
<input type="hidden" name="t" value="' . htmlspecialchars(get_http_var('t')) . '">
<input type="hidden" name="submitted" value="1">';

    if (!$details['add']) {
        if (isset($errors["alertsearch"])) {
            $PAGE->error_message($errors["alertsearch"]);
        }
?>

<div class="row">
<input type="text" name="alertsearch" id="alertsearch" value="<?php if ($details['alertsearch']) { echo htmlentities($details['alertsearch']); } ?>" size="30" style="font-size:150%">
</div>

<?php
    }

    if ($details['pid'])
        echo '<input type="hidden" name="pid" value="' . htmlspecialchars($details['pid']) . '">';
    if ($details['keyword'])
        echo '<input type="hidden" name="keyword" value="' . htmlspecialchars($details['keyword']) . '">';
    if ($details['pid'] || $details['keyword'])
        echo '<input type="hidden" name="only" value="1">';

    if (!$details['email_verified']) {
        if (isset($errors["email"]) && (get_http_var('submitted') || $details['add'])) {
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
        <input type="submit" class="submit" value="Search">
    </div>

    <div class="row">
<?php
    if (!$details['email_verified']) {
?>
        <p>If you join or sign in, you won't need to confirm your email
        address for every alert you set.
<?php
    }
    if (!$details['add']) {
?>
        <p>Please note that you should only enter one topic per alert - if
        you wish to receive alerts on more than one topic, or for more than
        one person, simply fill in this form as many times as you need.</p>
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

