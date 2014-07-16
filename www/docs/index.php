<?php

include_once '../includes/easyparliament/init.php';

//set page name (selects relevant bottom menu item)
$this_page = 'overview';

$PAGE->page_start();
$PAGE->supress_heading = true;
$PAGE->stripe_start("full");

$last_dates = array(); // holds the most recent data there is data for, indexed by type
$DEBATELIST = new DEBATELIST;
$LORDSDEBATELIST = new LORDSDEBATELIST;
$WHALLLIST = new WHALLLIST;
$WMSLIST = new WMSLIST;
$WRANSLIST = new WRANSLIST;
$COMMITTEE = new StandingCommittee();
$last_dates[1] = $DEBATELIST->most_recent_day();
$last_dates[101] = $LORDSDEBATELIST->most_recent_day();
$last_dates[4] = $WMSLIST->most_recent_day();
$last_dates[2] = $WHALLLIST->most_recent_day();
$last_dates[3] = $WRANSLIST->most_recent_day();
$last_dates[6] = $COMMITTEE->most_recent_day();

?>

<div class="welcome_col1">

<!-- Actions -->
<div id="welcome_uk" class="welcome_actions">

    <div>
        <h2>Your representative</h2>
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
            In <?php echo strtoupper(_htmlentities($THEUSER->postcode())); ?> (<a href="<?php echo $CHANGEURL->generate(); ?>">Change your postcode</a>)</p>
        <?php
                }
            }

            if ($pc_form) { ?>
                <form action="/postcode/" method="get">
                <p><strong>Find out about your <acronym title="Member of Parliament">MP</acronym>/
                <acronym title="Members of the Scottish Parliament">MSPs</acronym>/
                <acronym title="Members of the (Northern Irish) Legislative Assembly">MLAs</acronym></strong><br>
                <label for="pc">Enter your UK postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo _htmlentities($THEUSER->postcode()); ?>" class="text">&nbsp;&nbsp;<input type="submit" value=" Go " class="submit"></p>
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
        <form action="<?php echo $SEARCHURL->generate(); ?>" method="get" onsubmit="trackFormSubmit(this, 'Search', 'Submit', 'Home'); return false;">
            <h2><label for="search_input">Search, create an email alert or RSS feed</label></h2>
            <p>
                <input type="text" name="q" id="search_input" size="20" maxlength="100" class="text" value="<?=_htmlspecialchars(get_http_var("keyword"))?>">&nbsp;&nbsp;
                <input type="submit" value="Go" class="submit">
                <small>e.g. <em>word</em>, <em>phrase</em>, or <em>person</em> | <a href="/search/?adv=1">More options</a></small>
            </p>
            <?php if (count($popular_searches)) { ?>
                <p>
                    Popular searches today:
                    <?php foreach ($popular_searches as $i => $popular_search) {
                        echo $popular_search['display'];
                        if ($i < count($popular_searches)-1) print ', ';
                    } ?>
                </p>
            <?php } ?>
        </form>
    </div>

    <br class="clear">
</div>

<dl class="big-debates front">

<?php if (count($last_dates[3])) { ?>
<dt><a href="<?=$last_dates[3]['listurl']?>">Written answers</a>
<small><?=format_date($last_dates[3]['hdate'], LONGERDATEFORMAT); ?></small>
</dt>
<dd>The parliamentary question is a great way for MPs and peers to discover
information from ministers which the government may not wish to reveal.

<h3>Random recent written question</h3>
<?php

    $WRANSLIST->display('recent_wrans', array('days' => 7, 'num' => 1));
    $MOREURL = new URL('wransfront');
?>
    <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>">See more written answers</a></strong></p>
<?php
}

if (count($last_dates[4])) {
?>
<dt><a href="<?=$last_dates[4]['listurl']?>">Written ministerial statements</a>
<small><?=format_date($last_dates[4]['hdate'], LONGERDATEFORMAT); ?></small>
</dt>
<dd>Written ministerial statements were introduced to stop the practice of
having &ldquo;planted&rdquo; questions to elicit Government statements.

<h3 class="alt">Random recent written ministerial statement</h3>
<?php

$WMSLIST->display('recent_wms', array('days' => 7, 'num' => 1));
$MOREURL = new URL('wmsfront');

?>
    <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>">See more written statements</a></strong></p>
<?php
}

if (count($last_dates[6])) {
?>
<dt><a href="<?=$last_dates[6]['listurl']?>">Public Bill committees</a>
<small><?=format_date($last_dates[6]['hdate'], LONGERDATEFORMAT); ?></small>
</dt>
<dd>Previously called Standing Committees, these study proposed legislation (bills) in detail, debating each clause and sending amendments to the Commons.

<h3>Latest Public Bill Committee meetings</h3>
<?php

$COMMITTEE->display('recent_pbc_debates', array('num' => 5));
$MOREURL = new URL('pbcfront');

?>
    <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>">See more Public Bill committees</a></strong></p>

<?php } ?>

</div>
<div class="welcome_col2">

<div class="campaign">
    <p>
        What&rsquo;s up next: <span class="chev"><span class="hide">-</span></span>
    <a href="/calendar/">Upcoming</a>
    </p>
</div>

<?php
$PAGE->include_sidebar_template('front');
?>

<dl class="big-debates front">
<?php
if (count($last_dates[1])) {
?>
<dt><a href="<?=$last_dates[1]['listurl']?>">Commons debates</a>
<small><?=format_date($last_dates[1]['hdate'], LONGERDATEFORMAT); ?></small>
</dt>
<dd>The main chamber of the House of Commons is where debates are held
on a variety of topics, oral questions are answered, and new legislation is
debated.

<h3 class="alt">Random recent Commons debate</h3>
<?php
$DEBATELIST->display('recent_debates', array('days' => 7, 'num' => 1));
$MOREURL = new URL('debatesfront');
?>
        <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>">See more Commons debates</a></strong></p>
<?php
}

if (count($last_dates[2])) {
?>
<dt><a href="<?=$last_dates[2]['listurl']?>">Westminster Hall</a>
<small><?=format_date($last_dates[2]['hdate'], LONGERDATEFORMAT); ?></small>
</dt>
<dd>Westminster Hall is a secondary MP debating chamber, in a horseshoe
arrangement aimed at fostering a more constructive debate.

<h3>Random recent Westminster Hall debate</h3>
<?php
$WHALLLIST->display('recent_debates', array('days' => 7, 'num' => 1));
$MOREURL = new URL('whallfront');
?>
        <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>">See more Westminster Hall debates</a></strong></p>
<?php
}

if (count($last_dates[101])) {
?>
<dt><a href="<?=$last_dates[101]['listurl']?>">Lords debates</a>
<small><?=format_date($last_dates[101]['hdate'], LONGERDATEFORMAT); ?></small>
</dt>
<dd>Peers from all parties (and crossbench and bishops) scrutinise government
legislation and debate a variety of issues in the House of Lords main chamber.

<h3 class="alt">Random recent Lords debates</h3>
<?php
$LORDSDEBATELIST->display('recent_debates', array('days' => 7, 'num' => 1));
$MOREURL = new URL('lordsdebatesfront');
?>
        <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>">See more Lords debates</a></strong></p>
<?php
}
?>
</dl>

</div>

<?php
    $PAGE->stripe_end();

$PAGE->page_end();
