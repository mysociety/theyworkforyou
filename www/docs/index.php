<?php

$this_page = "home";

include_once "../includes/easyparliament/init.php";
include_once "../includes/easyparliament/member.php";

$PAGE->page_start();

$PAGE->stripe_start();

$message = $PAGE->recess_message();
if ($message != '') {
	print '<p id="warning">' . $message . '</p>';
}

///////////////////////////////////////////////////////////////////////////
//  SEARCH AND RECENT HANSARD

$HANSARDURL = new URL('hansard');
$MPURL = new URL('yourmp');
$PAGE->block_start(array ('id'=>'intro', 'title'=>'At TheyWorkForYou.com you can:'));
?>
						<ol>

<?php 

// Find out more about your MP / Find out more about David Howarth, your MP
function your_mp_bullet_point() {
	global $THEUSER, $MPURL;
	print "<li>";
	if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
		// User is logged in and has a postcode, or not logged in with a cookied postcode.
		
		// (We don't allow the user to search for a postcode if they
		// already have one set in their prefs.)
		
		if ($THEUSER->isloggedin()) {
			$CHANGEURL = new URL('useredit');
		} else {
			$CHANGEURL = new URL('userchangepc');
		}
		$MEMBER = new MEMBER(array ('postcode'=>$THEUSER->postcode()));
		$mpname = $MEMBER->first_name() . ' ' . $MEMBER->last_name();
		$former = "";
		$left_house = $MEMBER->left_house();
		if ($left_house[1]['date'] != '9999-12-31') {
			$former = 'former';
		}

		?>
	<p><a href="<?php echo $MPURL->generate(); ?>"><strong>Find out more about <?php echo $mpname; ?>, your <?= $former ?> MP</strong></a><br />
							In <?php echo strtoupper(htmlentities($THEUSER->postcode())); ?> (<a href="<?php echo $CHANGEURL->generate(); ?>">Change your postcode</a>)</p>
	<?php
		
	} else {
		// User is not logged in and doesn't have a personal postcode set.
		?>
							<form action="<?php echo $MPURL->generate(); ?>" method="get">
							<p><strong>Find out more about your MP</strong><br />
							<label for="pc">Enter your UK postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo htmlentities($THEUSER->postcode()); ?>" class="text" />&nbsp;&nbsp;<input type="submit" value=" GO " class="submit" /></p>
							</form>
	<?php
		if (!defined("POSTCODE_SEARCH_DOMAIN")) {
			print '<p align="right"><em>Postcodes are being mapped to a random MP</em></p>';
		}

	}
	print "</li>";
}

// Search / Search for 'mouse'
function search_bullet_point() {
	global $SEARCHURL;
	?> <li> <?php
	$SEARCHURL = new URL('search');
	?>
						<form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
						<p><strong><label for="s">Search<?=get_http_var("keyword") ? ' Hansard for \'' . htmlspecialchars(get_http_var("keyword")) . '\'' : ''?>:</label></strong>
					<input type="text" name="s" id="s" size="15" maxlength="100" class="text" value="<?=htmlspecialchars(get_http_var("keyword"))?>" />&nbsp;&nbsp;<input type="submit" value="SEARCH" class="submit" /></p>
                        <?
                            // Display popular queries
                            global $SEARCHLOG;
                            $popular_searches = $SEARCHLOG->popular_recent(10);
                            if (count($popular_searches) > 0) {
                                ?> <p>Popular searches today: <?
                                $lentotal = 0;
                                $correct_amount = array();
                                // Select a number of queries that will fit in the space
                                foreach ($popular_searches as $popular_search) {
                                    $len = strlen($popular_search['visible_name']);
                                    if ($lentotal + $len > 32) {
                                        continue;
                                    }
                                    $lentotal += $len;
                                    array_push($correct_amount, $popular_search['display']);
                                }
                                print implode(", ", $correct_amount);
                                ?> </p> <?
                            }
                        ?>
						</form>
						</li>
<?php
}

// Sign up to be emailed when something relevant to you happens in Parliament 
// Sign up to be emailed when 'mouse' is mentioned in Parliament
function email_alert_bullet_point() {
	if (get_http_var("keyword")) { ?>
		<li><p><a href="/alert?keyword=<?=htmlspecialchars(get_http_var('keyword'))?>&only=1"><strong>Sign up to be emailed when '<?=htmlspecialchars(get_http_var('keyword'))?>' is mentioned in Parliament</strong></a></p></li>
	<? } else { ?>
		<li><p><a href="/alert/"><strong>Sign up to be emailed when something relevant to you happens in Parliament</strong></a></p></li>
	<? } 
} 

// Comment on (recent debates)
function comment_on_recent_bullet_point() {
	global $hansardmajors;
?>
	<li><p><strong>Comment on:</strong></p>

<?php 
	$DEBATELIST = new DEBATELIST; $data[1] = $DEBATELIST->most_recent_day();
	$WRANSLIST = new WRANSLIST; $data[3] = $WRANSLIST->most_recent_day();
	$WHALLLIST = new WHALLLIST; $data[2] = $WHALLLIST->most_recent_day();
	$WMSLIST = new WMSLIST; $data[4] = $WMSLIST->most_recent_day();
	$LORDSDEBATELIST = new LORDSDEBATELIST; $data[101] = $LORDSDEBATELIST->most_recent_day();
	$NILIST = new NILIST; $data[5] = $NILIST->most_recent_day();
	foreach (array_keys($hansardmajors) as $major) {
		if (array_key_exists($major, $data)) {
			unset($data[$major]['listurl']);
			if (count($data[$major]) == 0) 
				unset($data[$major]);
		}
	}
	major_summary($data);
	?> </li> <?php 
}

if (get_http_var('keyword')) {
	// This is for links from Google adverts, where we want to
	// promote the features relating to their original search higher
	// than "your MP"
	search_bullet_point(); 
	email_alert_bullet_point();
	your_mp_bullet_point();
	comment_on_recent_bullet_point();
} else {
	your_mp_bullet_point();
	search_bullet_point(); 
	email_alert_bullet_point();
	comment_on_recent_bullet_point();
}

?>
						</ol>
<?php
$PAGE->block_end();

$includes = array(
	array (
		'type' => 'include',
		'content' => 'whatisthissite'
	),
	array (
		'type' => 'include',
		'content' => 'sitenews_recent'
	),
	array(
		'type' => 'include',
		'content' => 'comments_recent',
	)
);
$PAGE->stripe_end($includes);
$PAGE->page_end();

?>
