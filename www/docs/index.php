<?php

include_once "../includes/easyparliament/init.php";

$number_of_debates_to_show = 6;
$number_of_wrans_to_show = 5;

//set page name (selects relivant bottom menu item)
$this_page = 'overview';

//output header
$PAGE->page_start();
$PAGE->supress_heading = true;
$PAGE->stripe_start("full");

?>
<!-- Welcome -->
<!-- <div class="attention welcome">
    <h2>
        Welcome to TheyWorkForYou for the UK Parliament.
        <br>Find out what your MP is doing in your name, read debates and sign up for email alerts.
    </h2>
</div>
-->

<!-- Actions -->
<div id="welcome_uk" class="welcome_actions">
<!--
    <div id="" style="min-height: 27em; background: #ffff99;">
        <h2 align="center" style="padding-top:0; margin: 0.5em;">Election Quiz &ndash; it&rsquo;s job interview time for your next MP!</h2>

<p>
    Every few years, you get to choose who will <strong>make our laws</strong>, 
    and <strong>run our country</strong>.  
</p>

<p> 
    We asked individual candidates about <strong>local and national</strong> issues. 
    You can explore their answers.
</p>

<form method="post" action="http://election.theyworkforyou.com/quiz" id="postcode_form">
    <p id="postcode_line" style="font-size: 150%; text-align: center; padding-bottom: 0;">
        <strong style="display:block; text-align: left;"><label for="id_postcode">Enter your postcode:</label></strong> 
        <input id="id_postcode" type="text" name="postcode" size="8" style="border: 0.2em solid #88a465; font-size: 166%; margin: 0.25em 0 0 0;">
        <input type="submit" value="Go!" style="font-size: 166%">
    </p>
    <p style="margin-top:0; text-align: center">e.g. OX1 3DR <small>(don&rsquo;t worry about spaces or capitalisation)</small></p>
</form>

    </div>
-->

    <div style="background: #ffff99;">
        <h3>Your representative</h3>
        <?php
        
		$MPURL = new URL('yourmp');
        	global $THEUSER;

        	$pc_form = true;
        	if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
        		// User is logged in and has a postcode, or not logged in with a cookied postcode.

        		// (We don't allow the user to search for a postcode if they
        		// already have one set in their prefs.)

        		$MEMBER = new MEMBER(array ('postcode'=>$THEUSER->postcode(), 'house'=>1));
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
        	<p><a href="<?php echo $MPURL->generate(); ?>"><strong>Find out about <?php echo $mpname; ?>, your <?= $former ?> MP</strong></a><br>
        	In <?php echo strtoupper(htmlentities($THEUSER->postcode())); ?> (<a href="<?php echo $CHANGEURL->generate(); ?>">Change your postcode</a>)</p>
        <?php
        		}
        	}

        	if ($pc_form) { ?>
        		<form action="/postcode/" method="get">
        		<p><strong>Find out about your <acronym title="Member of Parliament">MP</acronym>/
        		<acronym title="Members of the Scottish Parliament">MSPs</acronym>/
        		<acronym title="Members of the (Northern Irish) Legislative Assembly">MLAs</acronym></strong><br>
        		<label for="pc">Enter your UK postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo htmlentities($THEUSER->postcode()); ?>" class="text">&nbsp;&nbsp;<input type="submit" value=" Go " class="submit"></p>
        		</form>
        	<?php
        	}
        	echo '<p>Read debates they&rsquo;ve taken part in, see how they voted, sign up for an email alert, and more.</p>';
        ?>
    </div>
    <!-- Search / alerts -->
    <div id="welcome_search">
        <?php
        	global $SEARCHURL;
        	global $SEARCHLOG;
        	$SEARCHURL = new URL('search');
            $popular_searches = $SEARCHLOG->popular_recent(10);
        ?>
        <form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
            <h3><label for="s">Search,  create an alert or RSS feed</label></h3>            
            <p>
                <input type="text" name="s" id="s" size="20" maxlength="100" class="text" value="<?=htmlspecialchars(get_http_var("keyword"))?>">&nbsp;&nbsp;
                <input type="submit" value="Go" class="submit">
                <br>
                <small>e.g. <em>word</em>, <em>phrase</em>, or <em>person</em> | <a href="/search/?adv=1">More options</a></small>
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

    <br class="clear">
</div>

<?php
    $PAGE->stripe_end();
    $PAGE->stripe_start("full");
?>

<!-- Campaign -->
<!--
<div class="campaign">
    <p>
        Become a TheyWorkForYou volunteer in your constituency <span class="chev"><span class="hide">-</span></span>
	<a href="http://www.democracyclub.org.uk/">Join DemocracyClub</a>
    </p>
</div>
-->

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
    <br class="clear">&nbsp;
</div>

<?php

$PAGE->stripe_end();
$PAGE->page_end();

