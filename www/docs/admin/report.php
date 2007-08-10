<?php

// This page is used to handle the whole admin side of examining and completing 
// comment reports.
// Pass it a GET string like ?rid=37&cid=54
// where rid is a report_id and cid is a comment_id.

include_once "../../includes/easyparliament/init.php";
include_once (INCLUDESPATH."easyparliament/commentreport.php");

$this_page = "admin_commentreport";

$PAGE->page_start();

$PAGE->stripe_start();

$menu = $PAGE->admin_menu();


//////////////////////////////////////////////////////////////////////////////////
// Set up the variables and objects we'll need on this page.


$report_id = get_http_var('rid');
$comment_id = get_http_var('cid');

if (!is_numeric($report_id) || !is_numeric($comment_id)) {
	// Exit.
	trigger_error("We need valid comment and report IDs.", E_USER_ERROR);
}

$COMMENT = new COMMENT($comment_id);

if ($COMMENT->exists() == false) {
	// Exit.
	trigger_error("This is an invalid comment ID", E_USER_ERROR);
}

$REPORT = new COMMENTREPORT($report_id);

$FORMURL = new URL($this_page);



//////////////////////////////////////////////////////////////////////////////////
// Check that the user is allowed to take action, and this report isn't locked.

if ($REPORT->locked() && $REPORT->lockedby() != $THEUSER->user_id()) {
	
	print "<p><strong>Someone else was examining this report at " . $REPORT->locked() . " so you can only look at it, not take any action. You could try again in a few minutes.</strong></p>\n";

	$COMMENT->display();

	$REPORT->display();
	
	$PAGE->stripe_end(array(
		array(
			'type'		=> 'html',
			'content'	=> $menu
		)
	));
	
	$PAGE->page_end();
	exit;

} elseif ($THEUSER->is_able_to('deletecomment')) {
	
	// Prevent anyone else from editing this report.
	$REPORT->lock();

}



//////////////////////////////////////////////////////////////////////////////////
// Now we decide what we're going to do on this page.
// You could read this if/else stuff from the bottom up to be honest...
	
if (get_http_var('resolve') != '') {

	// The user has reached the final stage, choosing what emails to send to who.
	// And here we finally delete the comment if needs be, and resolve the report.

	resolve($REPORT, $COMMENT);
	
	
} elseif (get_http_var('takingaction') == 'true') {

	// The user has chosen to delete or not delete the comment.
	// So we need to let them prepare the appropriate emails.

	// This also sets the comment's url, which we use further down!
	$COMMENT->display();
	
	$REPORT->display();

	if (get_http_var('yes') != '') {
		prepare_emails_for_deleting($REPORT, $COMMENT, $FORMURL);
	
	} else {
		prepare_emails_for_not_deleting($REPORT, $COMMENT, $FORMURL);
	}


} else {
	

	// The user is viewing a comment and its report.
	view($REPORT, $COMMENT, $FORMURL);
	
}

$PAGE->stripe_end(array(
	array(
		'type'		=> 'html',
		'content'	=> $menu
	)
));

$PAGE->page_end();


//////////////////////////////////////////////////////////////////////////////////
// Below are the functions for actually displaying each of the pages this file handles:
// view()
// prepare_emails_for_deleting()
// prepare_emails_for_not_deleting()
// resolve()


function view ($REPORT, $COMMENT, $FORMURL) {
	// First page - viewing the comment and its report.

	// This also sets the comment's url, which we use further down!
	$COMMENT->display();

	$REPORT->display();

	?>
	
	<p><strong>Do you wish to delete the comment?</strong></p>
	
	<form action="<?php echo $FORMURL->generate(); ?>" method="post">
		<p><input type="submit" name="yes" value=" Yes "> &nbsp;
		<input type="submit" name="no" value=" No ">
		<input type="hidden" name="takingaction" value="true">
		<input type="hidden" name="rid" value="<?php echo htmlentities($REPORT->report_id()); ?>">
		<input type="hidden" name="cid" value="<?php echo htmlentities($REPORT->comment_id()); ?>"></p>
	</form>
<?php
}



function get_template_contents($template) {
	// Fetches the contents of an email template so we can then
	// display it on the screen.
	// Shares some code with send_template_email() in utility.php
	// so may be scope for rationalising...
	global $PAGE;
	
	$filename = INCLUDESPATH . "easyparliament/templates/emails/" . $template . ".txt";

	if (!file_exists($filename)) {
		$PAGE->error_message("Sorry, we could not find the email template.");
		return false;
	}
	
	// Get the text from the template.
	$handle = fopen($filename, "r");
	$emailtext = fread($handle, filesize($filename));
	fclose($handle);
	
	return $emailtext;
}



function prepare_emails_for_deleting ($REPORT, $COMMENT, $FORMURL) {
	// From the view() function, the user has chosen to delete the comment.
	// Now they can prepare the appropriate emails, or choose not to send them.
	
	global $this_page;

#	$commentermail = preg_replace("/\n/", "<br>\n", get_template_contents('comment_deleted') );
	$commentermail = preg_replace('/^Subject:.*\n/', '', get_template_contents('comment_deleted') );
	$reportermail = preg_replace("/\n/", "<br>\n", get_template_contents('report_upheld') );

	?>
		<p><strong>You've chosen to delete this comment.</strong> You can now send an email to both the person who posted the comment, and the person who made the report. Uncheck a box to prevent an email from being sent. The comment will not be deleted until you click the button below.</p>

		<form action="<?php echo $FORMURL->generate(); ?>" method="post">
			
			<p><strong><input type="checkbox" name="sendtocommenter" value="true" checked id="sendtocommenter"> <label for="sendtocommenter">Send this email to the person who posted the comment:</label></strong></p>

<!--			<p class="email-template"><?php echo $commentermail; ?></p> -->
			<textarea rows="20" cols="80" name="commentermail"><?php echo $commentermail; ?></textarea>

<!--			<p>Enter a reason to replace {DELETEDREASON}: <input type="text" name="deletedreason" size="40"></p> -->

			<p>&nbsp;<br><strong><input type="checkbox" name="sendtoreporter" value="true" checked id="sendtoreporter"> <label for="sendtoreporter">Send this email to the person who made the report:</label></strong></p>

			<p class="email-template"><?php echo $reportermail; ?></p>

			<p><input type="submit" name="resolve" value=" Delete comment ">
			<input type="hidden" name="deletecomment" value="true">
			<input type="hidden" name="rid" value="<?php echo htmlentities($REPORT->report_id()); ?>">
			<input type="hidden" name="cid" value="<?php echo htmlentities($REPORT->comment_id()); ?>"></p>
		</form>
<?php

}



function prepare_emails_for_not_deleting($REPORT, $COMMENT, $FORMURL) {
	// From the view() function, the user has chosen NOT to delete the comment.
	// Now they can prepare the appropriate emails, or choose not to send them.
	
	global $this_page;

	$reportermail = preg_replace("/\n/", "<br>\n", get_template_contents('report_declined') );
		
	?>
		<p><strong>You have chosen not to delete this comment.</strong> You can now send an email to the person who made the report (uncheck the box to send no email). The report will not be resolved until you click the button below.</p>
		
		<form action="<?php echo $FORMURL->generate(); ?>" method="post">
			<p>&nbsp;<br><strong><input type="checkbox" name="sendtoreporter" value="true" checked id="sendtoreporter"> <label for="sendtoreporter">Send this email to the person who reported the comment:</label></strong></p>

			<p class="email-template"><?php echo $reportermail; ?></p>
			
			<p>Enter a reason to replace {REASON}: <input type="text" name="declinedreason" size="40"></p>
			
			<p><input type="submit" name="resolve" value=" Resolve this report ">
			<input type="hidden" name="deletecomment" value="false">
			<input type="hidden" name="rid" value="<?php echo htmlentities($REPORT->report_id()); ?>">
			<input type="hidden" name="cid" value="<?php echo htmlentities($REPORT->comment_id()); ?>"></p>
		</form>
<?php
}




function resolve ($REPORT, $COMMENT) {
	// The user has chosen to either delete or not delete the comment.
	// And we might be sending emails.
	
	global $PAGE;
		
	if (get_http_var('deletecomment') == 'true') {
		$upheld = true;
	} else {
		$upheld = false;
	}	

	$success = $REPORT->resolve ($upheld, $COMMENT);

	if ($success) {
	
		if ($upheld == true) {
			print "<p>The comment has been deleted.</p>\n";
		}
		
		print "<p>The report has been resolved.</p>\n";
		
		
		if (get_http_var('sendtoreporter') == 'true') {
			// We're sending an email to the reporter.
			// Either approving or declining what they suggested.
			if ($REPORT->user_id() > 0) {
				// The reporting user was logged in at the time, 
				// so get their email address.
				$USER = new USER;
				$USER->init( $REPORT->user_id() );
				$email = $USER->email();
			} else {
				// Non-logged-in user; they should have left their address.
				$email = $REPORT->email();
			}
			
			// Prepare the data needed for either email.
			$data = array (
				'to' 			=> $email
			);
			$merge = array (
				'FIRSTNAME' 	=> $REPORT->firstname(),
				'LASTNAME' 		=> $REPORT->lastname(),
				'REPORTBODY' 	=> strip_tags($REPORT->body())
			);
			
			// Add stuff specific to each type of email.
			if ($upheld == true) {
				$data['template'] = 'report_upheld';
			
			} else {
				$data['template'] = 'report_declined';
				$merge['COMMENTURL'] = 'http://' . DOMAIN . $COMMENT->url();
				$merge['REASON'] = get_http_var('declinedreason');
			}
			
			$success = send_template_email($data, $merge);

			
			if ($success) {
				print "<p>An email has been sent to the person who made the report.</p>\n";
			} else {
				$PAGE->error_message("Failed when sending an email to the person who made the report.");
			}

		}
		
		if (get_http_var('sendtocommenter') == 'true') {
			// We're telling the commenter that their comment has been deleted.
			$USER = new USER;
			$USER->init($COMMENT->user_id());

			// Create the URL for if a user wants to return and post another comment.
			// Remove the anchor for their now deleted comment.
			$addcommentsurl = 'http://' . DOMAIN . preg_replace("/#.*$/", '#addcomment', $COMMENT->url());
			
			$data = array (
				'to' => $USER->email(),
				'template' => 'comment_deleted_blank',
				'subject' => 'One of your comments has been deleted',
			);
			$merge = array (
				'REPLYBODY' => get_http_var('commentermail'),
				'FIRSTNAME' 	=> $USER->firstname(),
				'LASTNAME' 	=> $USER->lastname(),
#				'DELETEDREASON'	=> get_http_var('deletedreason'),
				'ADDCOMMENTURL'	=> $addcommentsurl,
				'COMMENTBODY'	=> strip_tags($COMMENT->body())
			);
			
			// We only send this email if a comment has been deleted.
			$success = send_template_email($data, $merge);
	
			if ($success) {
				print "<p>An email has been sent to the person who posted the comment.</p>\n";
			} else {
				$PAGE->error_message("Failed when sending an email to the person who posted the comment.");
			}
		}
	}

	$URL = new URL('admin_home');
	
	print '<p><a href="' . $URL->generate() . '">Back</a></p>';
}


?>
