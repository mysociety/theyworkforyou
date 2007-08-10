<?php

$this_page = "home";

include_once "../includes/easyparliament/init.php";
include_once "../includes/easyparliament/member.php";

$PAGE->page_start();

$PAGE->stripe_start();

///////////////////////////////////////////////////////////////////////////
//  SEARCH AND RECENT HANSARD


// Get the dates, timestamps and links for the most recent debates and wrans.
$DEBATELIST = new DEBATELIST;
$debatesdata = $DEBATELIST->most_recent_day();

$WRANSLIST = new WRANSLIST;
$wransdata = $WRANSLIST->most_recent_day();

$WHALLLIST = new WHALLLIST;
$whalldata = $WHALLLIST->most_recent_day();

$WMSLIST = new WMSLIST;
$wmsdata = $WMSLIST->most_recent_day();

if (count($debatesdata) > 0 && count($wransdata) > 0 && count($whalldata) > 0 && count($wmsdata) > 0) {
	// Links to Debates and Wrans.
	$debatestext = '<a href="' . $debatesdata['listurl'] . '">Debates</a>';
	$wranstext = '<a href="' . $wransdata['listurl'] . '">Written Answers</a>';
	$whalltext = '<a href="' . $whalldata['listurl'] . '">Westminster Hall debates</a>';
	$wmstext = '<a href="' . $wmsdata['listurl'] . '">Written Ministerial Statements</a>';

	// Now we work out whether the debates/wrans are from yesterday or another day...
	// And create the appropriate text ($daytext) to display accordingly.
	
	$todaystime = gmmktime(0, 0, 0, date('m'), date('d'), date('Y'));
	
	if ($debatesdata['hdate'] == $wransdata['hdate'] && $debatesdata['hdate'] == $whalldata['hdate'] && $debatesdata['hdate'] == $wmsdata['hdate']) {
		// They're on the same day, which is nice, and most common.

		if ($todaystime - $debatesdata['timestamp'] == 86400) {
			$daytext = "yesterday's";
			
		} elseif ($todaystime - $debatesdata['timestamp'] <= (6 * 86400)) {
			// Less than a week ago, so like "last Tuesday's".
			$daytext = gmdate('l', $debatesdata['timestamp']) . "'s";
		
		} else {

			// Over a week ago.
			$daytext = "the most recent ";
		}
		
	} else {
		// Debates and Wrans are from different dates. We'll just do this for now:
		$daytext = "the most recent ";
	}
	
#	$hansardline = "Comment on $daytext <ul><li>$debatestext</li><li>$wranstext</li><li>$whalltext</li><li>$wmstext</li></ul>";
	$hansardline = "Comment on $daytext $debatestext, $wranstext, $whalltext, and $wmstext";
	
} else {
	// We didn't get some or all of the data, so just...
	$hansardline = "Comment on events in parliament";
}




$HANSARDURL = new URL('hansard');
$MPURL = new URL('yourmp');

$PAGE->block_start(array ('id'=>'intro', 'title'=>'Election special! Find out how they performed for YOU:'));
?>
						<ol>
	
						<li>
<?php
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
	?>
	  <p><a href="<?php echo $MPURL->generate(); ?>"><strong>Find out more about how <?php echo $mpname; ?>, your ex-MP, represented you over the last parliament</strong></a><br>
						In <?php echo strtoupper(htmlentities($THEUSER->postcode())); ?> (<a href="<?php echo $CHANGEURL->generate(); ?>">Change your postcode</a>)</p>
<?php
	
} else {
	// User is not logged in and doesn't have a personal postcode set.
	?>
						<form action="<?php echo $MPURL->generate(); ?>" method="get">
    <p><strong>Now that the election has been called, you can use this site to find out what your ex-MP did throughout the last parliament. We have performance stats, speeches, voting records and more...</strong><br>
						<label for="pc">Enter your UK postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo htmlentities($THEUSER->postcode()); ?>" class="text">&nbsp;&nbsp;<input type="submit" value=" GO " class="submit"></p>
						</form>
<?php
}
?>
						</li>
						
						<li>
<?php
	$SEARCHURL = new URL('search');
	?>
						<form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
						<p><strong>Search everything said in Parliament since 2001, or for an ex-MP or constituency</strong><br>
						<label for="s">Type what you are looking for:</label>&nbsp; <input type="text" name="s" id="s" size="15" maxlength="100" class="text">&nbsp;&nbsp;<input type="submit" value="SEARCH" class="submit"></p>
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
#                                        continue;
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

	// Get the data for the Busiest Debate stuff.
	$biggest_data = $DEBATELIST->biggest_debates();
	// Because it could return multiple debates, and we only want one.
	$biggest_data = $biggest_data['data'][0];
	?>
						
						</ol>		
<a href="nonelection.php"> old homepage</a>

<?php
$PAGE->block_end();

///////////////////////////////////////////////////////////////////////////
//  RECENT COMMENTS

// Most recent comments.


///////////////////////////////////////////////////////////////////////////
//  MOST INTERESTING DEBATES

//$DEBATELIST = new DEBATELIST;

// num is how many to display.
// days is over what period we're looking for the most interesting debates.
//$DEBATELIST->display('recent_mostvotes', array('num'=>5, 'days'=>21));


///////////////////////////////////////////////////////////////////////////
//  MOST RECENT GLOSSARY ENTRY

// Temporary HTML...
$URL = new URL('glossary');

//$PAGE->block_start(array('title'=>'Latest glossary entry'));
?>
	<!--		
						<p><strong>A tricksy word</strong> It will go right here.</p>
						
						<p><a href="<?php echo $URL->generate(); ?>">See more words from the glossary</a></p>
-->
<?php
//$PAGE->block_end();

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => 'whatisthissite'
	),
	array (
		'type' => 'include',
		'content' => 'sitenews_recent'
	)
));


$PAGE->page_end();

?>
