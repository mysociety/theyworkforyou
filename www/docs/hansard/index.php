<?php

include_once "../../includes/easyparliament/init.php";

$number_of_debates_to_show = 6;
$number_of_wrans_to_show = 5;

if (($date = get_http_var('d')) && preg_match('#^\d\d\d\d-\d\d-\d\d$#', $date)) {
	$this_page = 'hansard_date';
	$PAGE->set_hansard_headings(array('date'=>$date));
	$URL = new URL($this_page);
	$db = new ParlDB;
	$q = $db->query("SELECT MIN(hdate) AS hdate FROM hansard WHERE hdate > '$date'");
	if ($q->rows() > 0 && $q->field(0, 'hdate') != NULL) {
		$URL->insert( array( 'd'=>$q->field(0, 'hdate') ) );
		$title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
		$nextprevdata['next'] = array (
			'hdate'         => $q->field(0, 'hdate'),
			'url'           => $URL->generate(),
			'body'          => 'Next day',
			'title'         => $title
		);
	}
	$q = $db->query("SELECT MAX(hdate) AS hdate FROM hansard WHERE hdate < '$date'");
	if ($q->rows() > 0 && $q->field(0, 'hdate') != NULL) {
		$URL->insert( array( 'd'=>$q->field(0, 'hdate') ) );
		$title = format_date($q->field(0, 'hdate'), SHORTDATEFORMAT);
		$nextprevdata['prev'] = array (
			'hdate'         => $q->field(0, 'hdate'),
			'url'           => $URL->generate(),
			'body'          => 'Previous day',
			'title'         => $title
		);
	}
	#	$year = substr($date, 0, 4);
	#	$URL = new URL($hansardmajors[1]['page_year']);
	#	$URL->insert(array('y'=>$year));
	#	$nextprevdata['up'] = array (
		#		'body'  => "All of $year",
		#		'title' => '',
		#		'url'   => $URL->generate()
		#	);
	$DATA->set_page_metadata($this_page, 'nextprev', $nextprevdata);
	$PAGE->page_start();
	$PAGE->stripe_start();
	include_once INCLUDESPATH . 'easyparliament/recess.php';
	$time = strtotime($date);
	$dayofweek = date('w', $time);
	$recess = recess_prettify(date('j', $time), date('n', $time), date('Y', $time), 1);
	if ($recess[0]) {
		print '<p>The Houses of Parliament are in their ' . $recess[0] . ' at this time.</p>';
	} elseif ($dayofweek == 0 || $dayofweek == 6) {
		print '<p>The Houses of Parliament do not meet at weekends.</p>';
	} else {
		$data = array(
			'date' => $date
		);
		foreach (array_keys($hansardmajors) as $major) {
			$URL = new URL($hansardmajors[$major]['page_all']);
			$URL->insert(array('d'=>$date));
			$data[$major] = array('listurl'=>$URL->generate());
		}
		major_summary($data);
	}
	$PAGE->stripe_end(array(
		array (
			'type' 	=> 'nextprev'
		),
	));
	$PAGE->page_end();
	exit;
}

    //set page name (selects relivant bottom menu item)
    $this_page = 'overview';

    //output header
    $PAGE->page_start();
    $PAGE->supress_heading = true;
    $PAGE->stripe_start("full", '');
?>
<!-- Welcome -->
<div class="attention">
    <h2>
        Welcome to They Work For You for the UK Parliament.
        <br/>
        Find out what your MP is doing in your name, read debates and signup for email alerts.
    </h2>
</div>

<!-- Actions -->
<div class="col3">
    <!-- Search / alerts -->
    <div>
        <h3>Search or create an alert</h3>
        <?php
        	global $SEARCHURL;
        	global $SEARCHLOG;
        	$SEARCHURL = new URL('search');
            $popular_searches = $SEARCHLOG->popular_recent(10);
        ?>
        <form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
            <p>
                <label for="s"><strong>Search</strong> (e.g. for a word, phrase, or person):</label>
                <input type="text" name="s" id="s" size="20" maxlength="100" class="text" value="<?=htmlspecialchars(get_http_var("keyword"))?>">&nbsp;&nbsp;
                <input type="submit" value="Search" class="submit">
                <small>(<a href="/search/?adv=1">Advanced Search</a>)</small>
            </p>
            <?php if (count($popular_searches) > 0) { ?>
                <p>
                    Popular searches today: 
                    <?php foreach ($popular_searches as $popular_search) { ?>
                        <a href="<?php echo $popular_search['url']?>"><?php echo $popular_search['display']?></a>
                    <?php } ?>
                </p>
            <?php } ?>
        </form>
    </div>
    <div>
        <h3>Busy debates</h3>        
        <?php
            $DEBATELIST = new DEBATELIST;
            $DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>$number_of_debates_to_show));
        ?>
    </div>
    <div>
        <h3>Your MP</h3>
        <?php
        
        	global $THEUSER, $MPURL;

        	$pc_form = true;
        	if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
        		// User is logged in and has a postcode, or not logged in with a cookied postcode.

        		// (We don't allow the user to search for a postcode if they
        		// already have one set in their prefs.)

        		$MEMBER = new MEMBER(array ('postcode'=>$THEUSER->postcode()));
        		if ($MEMBER->valid) {
        			$pc_form = false;
        			if ($THEUSER->isloggedin()) {
        				$CHANGEURL = new URL('useredit');
        			} else {
        				$CHANGEURL = new URL('userchangepc');
        			}
        			$mpname = $MEMBER->first_name() . ' ' . $MEMBER->last_name();
        			$former = "";
        			$left_house = $MEMBER->left_house();
        			if ($left_house[1]['date'] != '9999-12-31') {
        				$former = 'former';
        			}
        ?>
        	<p><a href="<?php echo $MPURL->generate(); ?>"><strong>Find out more about <?php echo $mpname; ?>, your <?= $former ?> MP</strong></a><br>
        	In <?php echo strtoupper(htmlentities($THEUSER->postcode())); ?> (<a href="<?php echo $CHANGEURL->generate(); ?>">Change your postcode</a>)</p>
        <?php
        		}
        	}

        	if ($pc_form) { ?>
        		<form action="/postcode/" method="get">
        		<p><strong>Find out more about your <acronym title="Member of Parliament">MP</acronym>/
        		<acronym title="Members of the Scottish Parliament">MSPs</acronym>/
        		<acronym title="Members of the (Northern Irish) Legislative Assembly">MLAs</acronym></strong><br>
        		<label for="pc">Enter your UK postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo htmlentities($THEUSER->postcode()); ?>" class="text">&nbsp;&nbsp;<input type="submit" value=" Go " class="submit"></p>
        		</form>
        	<?php
        		if (!defined("POSTCODE_SEARCH_DOMAIN") || !POSTCODE_SEARCH_DOMAIN) {
        			print '<p align="right"><em>Postcodes are being mapped to a random MP</em></p>';
        		}
        	}
        	print "</li>";
        
        
        ?>
    </div>
</div>

<?php
    $PAGE->stripe_end();
    $PAGE->stripe_start("full", '', true);    
?>

<!-- Latest in parliament -->
<div class="latest col3">
    <h3>Recently in the UK Parliament</h3>
    <div>
        <?php
    
            //Latest activity (column 1)
            $DEBATELIST = new DEBATELIST; 
            $LORDSDEBATELIST = new LORDSDEBATELIST;

            $last_dates = array(); // holds the most recent data there is data for, indexed by type    
            $last_dates[1] = $DEBATELIST->most_recent_day();    
            $last_dates[101] = $LORDSDEBATELIST->most_recent_day();

            //get html
            $latest_html = major_summary($last_dates, false);
            echo $latest_html;
        ?>
    </div>
    <div>
        <?php
            //Latest activity (column 2)  
            $WHALLLIST = new WHALLLIST;                   
            $WMSLIST = new WMSLIST; 
            $last_dates = array();
            $last_dates[4] = $WMSLIST->most_recent_day();       
            $last_dates[2] = $WHALLLIST->most_recent_day();                     
            
            //get html
            $latest_html = major_summary($last_dates, false);
            echo $latest_html;        
        ?>
    </div>
    <div>
        <?php    
            //Latest activity (column 3)
            $WRANSLIST = new WRANSLIST;    
            $last_dates = array();
            $last_dates[3] = $WRANSLIST->most_recent_day();        
    
            /*
            	foreach (array_keys($hansardmajors) as $major) {
            		if (array_key_exists($major, $data)) {
            			unset($data[$major]['listurl']);
            			if (count($data[$major]) == 0) 
            				unset($data[$major]);
            		}
            	}
        	*/
            //get debates html
            $latest_html = major_summary($last_dates, false);
            echo $latest_html;
        ?>    
    </div>
    <br class="clear" />
</div>
<?php

$PAGE->stripe_end();
$PAGE->page_end();
exit;    
?>        

<?php
/*
    // Page title will appear here.
    $message = $PAGE->recess_message();
    if ($message != '') {
    	$PAGE->stripe_start('head-1');
    	print "<p><strong>$message</strong></p>\n";
    	$PAGE->stripe_end();
    }
    $PAGE->stripe_start();
*/
?>

				<h3>Busiest House of Commons debates from the most recent week</h3>
<?php
$DEBATELIST = new DEBATELIST;
$DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>$number_of_debates_to_show));

$MOREURL = new URL('debatesfront');
$anchor = $number_of_debates_to_show + 1;
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more debates</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "hocdebates_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_hocdebates"
	)
));

$PAGE->stripe_start();

?>
				<h3>Busiest House of Lords debates from the most recent week</h3>
<?php
$DEBATELIST = new LORDSDEBATELIST;
$DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>$number_of_debates_to_show));

$MOREURL = new URL('lordsdebatesfront');
$anchor = $number_of_debates_to_show + 1;
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more debates</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "holdebates_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_holdebates"
	)
));

$PAGE->stripe_start();
?>
				<h3>Some recent written answers</h3>
<?php

$WRANSLIST = new WRANSLIST;
$WRANSLIST->display('recent_wrans', array('days'=>7, 'num'=>$number_of_wrans_to_show));

$MOREURL = new URL('wransfront');
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>">See more written answers</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "wrans_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_wrans"
	)
));

$PAGE->stripe_start();

?>
				<h3>Busiest Westminster Hall debates from the most recent week</h3>
<?php
$WHALLLIST = new WHALLLIST;
$WHALLLIST->display('biggest_debates', array('days'=>7, 'num'=>$number_of_debates_to_show));

$MOREURL = new URL('whallfront');
$anchor = $number_of_debates_to_show + 1;
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>#d<?php echo $anchor; ?>">See more debates</a></strong></p>
<?php

$PAGE->stripe_end(array(
	array (
		'type' => 'include',
		'content' => "whalldebates_short"
	),
	array (
		'type' => 'include',
		'content' => "calendar_whalldebates"
	)
));

$PAGE->stripe_start();

?>
				<h3>Some recent Written Ministerial Statements</h3>
<?php
$WMSLIST = new WMSLIST;
$WMSLIST->display('recent_wms', array('days'=>7, 'num'=>$number_of_wrans_to_show));
$MOREURL = new URL('wmsfront');
?>
				<p><strong><a href="<?php echo $MOREURL->generate(); ?>">See more written ministerial statements</a></strong></p>
<?php
$PAGE->stripe_end(array(
	array( 'type' => 'include', 'content' => 'wms_short' ),
	array( 'type' => 'include', 'content' => 'calendar_wms' )
));

$PAGE->stripe_start();
?>

<p>Still to come: Select Committees, and much more...</p>

<?php
$PAGE->stripe_end();

$PAGE->page_end();
?>
