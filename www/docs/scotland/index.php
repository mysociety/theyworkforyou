<?php

include_once "../../includes/easyparliament/init.php";

$this_page = 'spoverview';
$PAGE->page_start();
$PAGE->stripe_start('full');
$SEARCHURL = new URL('search');

?>

<h2>Scottish Parliament</h2>

<div class="welcome_col1">

<div id="welcome_scotland" class="welcome_actions">

    <div>
        <h3>Your representative</h3>
            <form action="/postcode/" method="get">
            <p><strong>Find out about your <acronym title="Members of the Scottish Parliament">MSPs</acronym></strong><br>
            <label for="pc">Enter your Scottish postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo htmlentities($THEUSER->postcode()); ?>" class="text">&nbsp;&nbsp;<input type="submit" value=" Go " class="submit"></p>
            </form>
        <p>Read debates they&rsquo;ve taken part in, see how they voted, sign up for an email alert, and more.</p>
    </div>

    <!-- Search / alerts -->
    <div id="welcome_search">
        <form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
            <h3><label for="s">Search,  create an alert or RSS feed</label></h3>
            <p>
                <input type="text" name="s" id="s" size="20" maxlength="100" class="text" value="<?=htmlspecialchars(get_http_var("keyEord"))?>">&nbsp;&nbsp;
                <input type="hidden" name="section" value="scotland">
                <input type="submit" value="Go" class="submit">
                <small>e.g. a <em>word</em>, <em>phrase</em>, or <em>person</em> | <a href="/search/?adv=1">More options</a></small>
            </p>
        </form>
    </div>

    <a class="credit" href="http://www.flickr.com/photos/itmpa/4198176622/">Photo by Tom Parnell</a>

    <br class="clear">
</div>

    <h3>Some recent Scottish Parliament written answers</h3>
<?php

$WRANSLIST = new SPWRANSLIST;
$WRANSLIST->display('recent_wrans', array('days' => 7, 'num' => 8));
$MOREURL = new URL('spwransfront');

?>
    <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>">See more written answers</a></strong></p>

</div>

<div class="welcome_col2">

<?php

$PAGE->include_sidebar_template('spdebates');

?>

<h3>Recent Scottish Parliament debates</h3>

<?php

$DEBATELIST = new SPLIST;
$DEBATELIST->display('recent_debates', array('days' => 7, 'num' => 10));
$MOREURL = new URL('spdebatesfront');

?>
        <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>">See more debates</a></strong></p>

</div>

<?php

$PAGE->stripe_end();
$PAGE->page_end();

