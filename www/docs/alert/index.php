<?php

// This is the main file allowing users to manage email alerts.
// It is based on the file /user/index.php.
// The alerts depend on the class ALERT which is established in /includes/easyparliament/alert.php
// .

/* What happens?

There is only one function here which is to add an alert.

Alerts are deleted through a confirmation token similar to that used to add alerts.

A link at the bottom of the page will send you a list of all your alerts with links to delete them if you wish.
	
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

$args = array( 'action' => $this_page);

// Put all the user-submitted data in an array.
$details = array();
if ($THEUSER->loggedin()) {
	$details['email'] = $THEUSER->email();
} else {
	$details["email"] = trim(get_http_var("email"));
}
$details['keyword'] = trim(get_http_var("keyword"));
$details['pid'] = trim(get_http_var("pid"));
$details['alertsearch'] = trim(get_http_var("alertsearch"));
if ($details['pid'] == 'Any') $details['pid'] = '';

// Check the input.
// If there are any errors with the submission, $errors (an array)
// will have elements. The keys will be the name of form elements,
// and the values will be text to display when we show the form again.
$errors = check_input($details);

// Do the search
if ($details['alertsearch']) {
    $details['members'] = search_member_db_lookup($details['alertsearch']);
    list ($details['constituencies'], $validpostcode) = search_constituencies_by_query($details['alertsearch']);
}

if (!sizeof($errors) && ( (get_http_var('submitted') && ($details['keyword'] || $details['pid']))
                       || (get_http_var('only') && ($details['keyword'] || $details['pid']))
		       || ($details['keyword'] && $details['pid']))) {
	add_alert( $details );
} else {
	$PAGE->page_start();
	$PAGE->stripe_start();
	$PAGE->block_start(array ('id'=>'alerts', 'title'=>'Request a TheyWorkForYou.com Email Alert'));
	display_search_form($details, $errors);
	$PAGE->block_end();	
	$end = array();
	if (!get_http_var('only') || !$details['pid'] || $details['keyword']) {
		$end[] = array('type' => 'include', 'content' => 'search');
	}
	$PAGE->stripe_end($end);
	$PAGE->page_end(); 
}


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
		$errors['pid'] = 'Please choose a valid person';

	if ((get_http_var('submitted') || get_http_var('only')) && !$details['pid'] && !$details['alertsearch'])
		$errors['alertsearch'] = 'Please enter what you want to be alerted about';

	if (strpos($details['alertsearch'], '..')) {
		$errors['alertsearch'] = 'You probably don&rsquo;t want a date range as part of your criteria, as you won&rsquo;t be alerted to anything new!';
	}

	// Send the array of any errors back...
	return $errors;
}


function add_alert ($details) {

	global $ALERT, $PAGE, $THEUSER, $this_page;

	$extra = null;

	// Instantiate an instance of ALERT
	$ALERT = new ALERT;

	$external_auth = auth_verify_with_shared_secret($details['email'], OPTION_AUTH_SHARED_SECRET, get_http_var('sign'));
	if ($external_auth) {
		$site = get_http_var('site');
		$extra = 'from_' . $site . '=1';
		$confirm = false;
	} elseif ($THEUSER->loggedin()) {
		$confirm = false;
	} else {
		$confirm = true;
	}

	// If this goes well, the alert will be added to the database and a confirmation email 
	// will be sent to them.
	$success = $ALERT->add ( $details, $confirm );
	
	// Display results message on blank page for both success and failure
	
	$this_page = 'alertwelcome';
	$URL = new URL('alertwelcome');
	$backlink = $URL->generate();
	$PAGE->page_start();
	$PAGE->stripe_start();
	
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
	$PAGE->message($message);
	if ($advert) {
		$advert_shown = alert_confirmation_advert($details);
		if ($extra) $extra .= "; ";
		$extra .= "advert=$advert_shown";
	}
	$PAGE->stripe_end();
	$PAGE->page_end($extra);
}

/*  This function creates the form for displaying an alert, prompts the user for input and creates
    the alert when submitted.
*/

function display_search_form ( $details = array(), $errors = array() ) {
	global $this_page, $ALERT, $PAGE, $THEUSER;
	$ACTIONURL = new URL($this_page);
	$ACTIONURL->reset();
?>

<?	if (!get_http_var('only')) { ?>
<p>Please note that you should only enter one topic per alert - if you wish to receive alerts on more than one topic, or for more than one person, simply fill in this form as many times as you need.</p>
<?	} ?>

	<form method="post" action="<?php echo $ACTIONURL->generate(); ?>">
	
	<?php	if (!$THEUSER->loggedin()) {
			if (isset($errors["email"]) && (get_http_var('submitted') || get_http_var('only'))) {
				$PAGE->error_message($errors["email"]);
			}
	?>
				<div class="row">
				<span class="label"><label for="email">Your email address:</label></span>
				<span class="formw"><input type="text" name="email" id="email" value="<?php if (isset($details["email"])) { echo htmlentities($details["email"]); } ?>" maxlength="255" size="30" class="form"></span>
				</div>
	<?php	}
        if ($details['members'] && $details['members']->rows() > 0) {
            echo '<ul class="hilites">';
            $q = $details['members'];
            $last_pid = null;
            for ($n=0; $n<$q->rows(); $n++) {
                if ($q->field($n, 'left_house') != '9999-12-31') {
                    if ($q->field($n, 'person_id') != $last_pid) {
                        $last_pid = $q->field($n, 'person_id');
                        echo '<li>';
                        $name = member_full_name($q->field($n, 'house'), $q->field($n, 'title'), $q->field($n, 'first_name'), $q->field($n, 'last_name'), $q->field($n, 'constituency') );
                        if ($q->field($n, 'house') == 1) {
                            echo $name . '(' . $q->field($n, 'constituency') . ') ';
                        } else {
                            echo $name;
                        }
                        echo $q->field($n, 'person_id');
                        echo "</li>\n";
                    }
                }
            }
            echo '</ul>';
        }

        if ($details['constituencies']) {
            echo '<ul class="hilites">';
            foreach ($details['constituencies'] as $constituency) {
                $MEMBER = new MEMBER(array('constituency'=>$constituency, 'house' => 1));
                $URL = new URL('mp');
                if ($MEMBER->valid) {
                    $URL->insert(array('m'=>$MEMBER->member_id()));
                }
                echo "<li>";
                echo $MEMBER->full_name();
                echo ' (' . htmlspecialchars($constituency) . ')';
                echo "</li>";
            }
            echo '</ul>';
        }
 
		if (!get_http_var('only') || !$details['pid']) {
			if (isset($errors["alertsearch"])) {
				$PAGE->error_message($errors["alertsearch"]);
			}
	?>
				<div class="row"> 
				<span class="label"><label for="keyword">Name, keyword or phrase you would like to receive alerts for:</label></span>
				<span class="formw"><input type="text" name="alertsearch" id="alertsearch" value="<?php if ($details['alertsearch']) { echo htmlentities($details['alertsearch']); } ?>" maxlength="255" size="30" class="form"></span>
				</div>
	<?php	}
		$submittext = "Search";
	?>
						
				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="submit" class="submit" value="<?php echo $submittext; ?>"><!-- this space makes the submit button appear on Mac IE 5! --> </span>
				</div>
	<?php	if (!$THEUSER->loggedin()) { ?>
				<div class="row">
				If you join or Sign in, you won't need to confirm your email address for every alert you set.
				</div>
	<?php	}
		if (get_http_var('sign'))
			echo '<input type="hidden" name="sign" value="' . htmlspecialchars(get_http_var('sign')) . '">';
		if (get_http_var('site'))
			echo '<input type="hidden" name="site" value="' . htmlspecialchars(get_http_var('site')) . '">';
		echo '<input type="hidden" name="submitted" value="true"> </form>';
} 


?>

