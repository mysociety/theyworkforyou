<?php

namespace MySociety\TheyWorkForYou\SectionView;

class NiView extends SectionView {
    protected $major = 5;
    protected $class = 'NILIST';

    protected function display_front() {
        if (get_http_var('more')) {
            parent::display_front();
        } else {
            $this->display_front_ni();
        }
    }

    protected function front_content() {
        echo '<h2>Busiest debates from the most recent month</h2>';
        $this->list->display('biggest_debates', array('days'=>30, 'num'=>20));
    }

    protected function display_front_ni() {
        global $this_page, $PAGE, $THEUSER, $SEARCHURL;

        $this_page = "nioverview";
        $PAGE->page_start();
        $PAGE->stripe_start('full');
        $SEARCHURL = new \MySociety\TheyWorkForYou\Url('search');
    ?>

    <div class="welcome_col1">

    <div id="welcome_ni" class="welcome_actions">

        <div>
            <h2>Your representative</h2>
                <form action="/postcode/" method="get">
                <p><strong>Find out about your <acronym title="Members of the Legislative Assembly">MLAs</acronym></strong><br>
                <label for="pc">Enter your postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo _htmlentities($THEUSER->postcode()); ?>" class="text">&nbsp;&nbsp;<input type="submit" value=" Go " class="submit"></p>
                </form>
            <p>Read debates they&rsquo;ve taken part in, see how they voted, sign up for an email alert, and more.</p>
        </div>
        <!-- Search / alerts -->
        <div id="welcome_search">
            <form action="<?php echo $SEARCHURL->generate(); ?>" method="get">
                <h2><label for="search_input">Search,  create an alert or RSS feed</label></h2>
                <p>
                    <input type="text" name="q" id="search_input" size="20" maxlength="100" class="text" value="<?=_htmlspecialchars(get_http_var("keyword"))?>">&nbsp;&nbsp;
                    <input type="hidden" name="section" value="ni">
                    <input type="submit" value="Go" class="submit">
                    <small>e.g. a <em>word</em>, <em>phrase</em>, or <em>person</em> | <a href="/search/?adv=1">More options</a></small>
                </p>
            </form>
        </div>

        <a class="credit" href="http://www.flickr.com/photos/lyng883/255250716/">Photo by Lyn Gateley</a>

        <br class="clear">
    </div>
    <?php
        $PAGE->include_sidebar_template('nidebates');
    ?>
    </div>

    <div class="welcome_col2">
        <h2>Recent Northern Ireland Assembly debates</h2>
    <?php
        $DEBATELIST = new \NILIST;
        $DEBATELIST->display('recent_debates', array('days' => 30, 'num' => 5));
        $MOREURL = new \MySociety\TheyWorkForYou\Url('nidebatesfront');
    ?>
        <p align="right"><strong><a href="<?php echo $MOREURL->generate(); ?>?more=1">See more debates</a></strong></p>
    </div>

    <?php
        $PAGE->stripe_end();
    }
}
