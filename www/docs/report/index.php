<?php

// For when a user reports a comment.

$this_page = "commentreport";

include_once "../../includes/easyparliament/init.php";
include_once (INCLUDESPATH."easyparliament/commentreport.php");

$PAGE->page_start();

$PAGE->stripe_start();

if (is_numeric(get_http_var('id'))) {
	// We have the id of a comment to report.

	$comment_id = get_http_var('id');

	$COMMENT = new COMMENT($comment_id);

	if ($COMMENT->exists() == false || !$COMMENT->visible()) {
		// This comment id didn't exist in the DB.
		trigger_error("There is no comment with an ID of '" . htmlentities($comment_id) . "'.", E_USER_ERROR);
	}
	
	// OK, we've got a valid comment ID.
	
	
	if (get_http_var('submitted') == true) {
		// The form has been submitted.
		
		$errors = array();
		
		if (get_http_var('body') == '') {
			$errors['body'] = "Please enter a reason why you think this comment is not appropriate.";
		}
		if (!$THEUSER->isloggedin()) {
			if (get_http_var('firstname') == '' || get_http_var('lastname') == '') {
				$errors['name'] = "Please let us know who you are!";
			}
			if (get_http_var('email') == '') {
				$errors['email'] = "Please enter your email address so we can contact you about this report.";
			}
		}
		
		if (count($errors) > 0) {
			display_form($COMMENT, $errors);
			
		} else {
		
			// Report this comment.
			
			$REPORT = new COMMENTREPORT;
		
			$reportdata = array (
				'body'		=> get_http_var('body'),
				'firstname'	=> get_http_var('firstname'),
				'lastname'	=> get_http_var('lastname'),
				'email'		=> get_http_var('email')
			);
			
			$success = $REPORT->create($COMMENT, $reportdata);
		
			if ($success) {
					?>
	<p><strong>The comment has been reported</strong> and a moderator will look into it as soon as possible. You should receive an email shortly confirming your report. Thanks for taking the time let us know about this.</p>
<?php
				if (!$THEUSER->isloggedin()) {
					$LOGINURL = new URL('userlogin');
					$JOINURL = new URL('userjoin');
					?>
	<p>By the way, if you <a href="<?php echo $LOGINURL->generate(); ?>">log in</a> before you make a report in future, you won't have to enter your name and email address each time!  (You'll need to <a href="<?php $JOINURL->generate(); ?>">join</a> if you haven't already.)</p>
<?php
				}
				if (get_http_var('ret') != '') {
					?>
	<p><a href="<?php echo htmlentities(get_http_var('ret')); ?>">Return to where you were.</a></p>
<?php
				}

	
			} else {
				$PAGE->error_message ("Sorry, we were unable to add the report to the database.");
			}
		}
		
	} else {
		display_form($COMMENT);
	}


} else {
	$PAGE->error_message("We need the ID of a comment before it can be reported.");	
}



function display_form($COMMENT, $errors=array()) {
	global $this_page, $THEUSER, $PAGE;
	
	?>
				<p>Here's the comment you're reporting. Please enter a brief reason why you think it should be deleted in the form beneath. Thanks for your help!</p>
<?php

	// First display the comment.
		
	$COMMENT->display();
	
	
	// Now display the form.
	
	$FORMURL = new URL($this_page);
	$FORMURL->remove(array('id'));

	?>
				<br />
				<form action="<?php echo $FORMURL->generate(); ?>" method="post">
<?php
	if ($THEUSER->isloggedin()) {
		?>
				<p><br /><strong>From:</strong> <?php echo htmlentities($THEUSER->firstname() . ' ' . $THEUSER->lastname()); ?></p>
<?php
	} else {
		// Not-logged-in user, so we want their name and email address.
		if (isset($errors['name'])) {
			$PAGE->error_message($errors['name']);
		}
		?>
				<div class="row">
				<span class="label"><label for="firstname">Your first name:</label></span>
				<span class="formw"><input type="text" name="firstname" id="firstname" value="" maxlength="50" size="30" class="form" /></span>
				</div>

				<div class="row">
				<span class="label"><label for="lastname">Your last name:</label></span>

				<span class="formw"><input type="text" name="lastname" id="lastname" value="" maxlength="50" size="30" class="form" /></span>
				</div>
<?php
		if (isset($errors['email'])) {
			$PAGE->error_message($errors['email']);
		}
		?>
				<div class="row">
				<span class="label"><label for="email">Email address:</label></span>
				<span class="formw"><input type="text" name="email" id="email" value="" maxlength="100" size="30" class="form" /></span>
				</div>
<?php
	}

	if (isset($errors['body'])) {
		$PAGE->error_message($errors['body']);
	}
	$RULESURL = new URL('houserules');
	?>
				<p style="clear: left;"><strong>Why should this comment be deleted?</strong><br />
				<small>Check our <a href="<?php echo $RULESURL->generate(); ?>">House Rules</a> and tell us why the comment breaks them.</small><br />
				<textarea name="body" rows="10" cols="45"><?php
	echo htmlentities(get_http_var('body'));
	?></textarea></p>
		
				<div class="row">
				<span class="label">&nbsp;</span>
				<span class="formw"><input type="submit" value="Send report" /></span>
				</div>
				
				<input type="hidden" name="submitted" value="true" />
				<input type="hidden" name="id" value="<?php echo htmlentities($COMMENT->comment_id()); ?>" />
<?php
	if (get_http_var('ret') != '') {
		// Where the user came from to get here.
		?>
				<input type="hidden" name="ret" value="<?php echo htmlentities(get_http_var('ret')); ?>" />
<?php
	}
	?>
				</form>

<?php
}

$PAGE->stripe_end();

$PAGE->page_end();

?>
