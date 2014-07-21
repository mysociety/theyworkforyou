<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";

// "Old-style" debate pages, showing a debate by a given date and column,
//  a calendar of debates for a particular year, or a list of recent debates.

// Showing a particular debate (or part of a debate) is handled by
// www/docs/debates/index.php which renders a "new-style" page.

if (get_http_var("d") != "") {
    if (get_http_var('c') != '') {
        $this_page = 'debatescolumn';
        $args = array(
            'date' => get_http_var('d'),
            'column' => get_http_var('c')
        );
        $LIST = new DEBATELIST;
        $LIST->display('column', $args);
    } else {
    // We have a date. so show all debates on this day.

    $this_page = "debatesday";

    $args = array (
        'date' => get_http_var('d')
    );

    $LIST = new DEBATELIST;

    $LIST->display('date', $args);
    }

} elseif (get_http_var('y') != '') {

    // Show a calendar for a particular year's debates.

    $this_page = 'debatesyear';

    if (is_numeric(get_http_var('y'))) {
        $DATA->set_page_metadata($this_page, 'title', get_http_var('y'));
    }

    $PAGE->page_start();

    $PAGE->stripe_start();

    $args = array (
        'year' => get_http_var('y')
    );

    $LIST = new DEBATELIST;

    $LIST->display('calendar', $args);

    $PAGE->stripe_end(array(
        array (
            'type' => 'nextprev'
        ),
        array (
            'type' => 'include',
            'content' => 'minisurvey'
        ),
        array (
            'type' => 'include',
            'content' => "hocdebates"
        )
    ));

} else {
    // No date or debate id. Show some recent debates

    $this_page = "alldebatesfront";
    $PAGE->page_start();
    $PAGE->stripe_start();
?>
    <h2>Recent House of Commons debates</h2>
<?php

    $DEBATELIST = new DEBATELIST;
    $DEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>10));

    $rssurl = $DATA->page_metadata($this_page, 'rss');
    $PAGE->stripe_end(array(
        # XXX When this is three columns, not one, put this search at the top spanning...
        array (
            'type' => 'include',
            'content' => 'minisurvey'
        ),
        array(
            'type' => 'html',
            'content' => '
<div class="block">
<h4>Search debates</h4>
<div class="blockbody">
<form action="/search/" method="get">
<p><input type="text" name="q" id="search_input" value="" size="40"> <input type="submit" value="Go">
<br><input type="checkbox" name="section[]" value="debates" checked id="section_commons">
<label for="section_commons">Commons</label>
<input type="checkbox" name="section[]" value="whall" checked id="section_whall">
<label for="section_whall">Westminster Hall</label>
<input type="checkbox" name="section[]" value="lords" checked id="section_lords">
<label for="section_lords">Lords</label>
</p>
</form>
</div>
</div>
',
    ),
        array (
            'type' => 'include',
            'content' => 'calendar_hocdebates'
        ),
        array (
            'type' => 'include',
            'content' => "hocdebates"
        ),
        array (
            'type' => 'html',
            'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img align="middle" src="http://www.theyworkforyou.com/images/rss.gif" border="0" alt="RSS feed"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></p>
</div>'
        )
    ));

    $this_page = "whallfront";
    $PAGE->page_start();
    $PAGE->stripe_start();
?>
    <h2>Recent Westminster Hall debates</h2>
<?php

    $WHALLLIST = new WHALLLIST;
    $WHALLLIST->display('biggest_debates', array('days'=>7, 'num'=>10));

    $rssurl = $DATA->page_metadata($this_page, 'rss');
    $PAGE->stripe_end(array(
        array (
            'type' => 'include',
            'content' => 'minisurvey'
        ),
        array (
            'type' => 'include',
            'content' => 'calendar_whalldebates'
        ),
        array (
            'type' => 'include',
            'content' => "whalldebates"
        ),
        array (
            'type' => 'html',
            'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img alt="RSS feed" border="0" align="middle" src="http://www.theyworkforyou.com/images/rss.gif"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></p>
</div>'
        )
    ));

    $this_page = "lordsdebatesfront";
    $PAGE->page_start();
    $PAGE->stripe_start();
?>
    <h2>Recent House of Lords debates</h2>
<?php

    $LORDSDEBATELIST = new LORDSDEBATELIST;
    $LORDSDEBATELIST->display('biggest_debates', array('days'=>7, 'num'=>10));

    $rssurl = $DATA->page_metadata($this_page, 'rss');
    $PAGE->stripe_end(array(
        array (
            'type' => 'nextprev'
        ),
        array (
            'type' => 'include',
            'content' => 'minisurvey'
        ),
        array (
            'type' => 'include',
            'content' => 'calendar_holdebates'
        ),
        array (
            'type' => 'include',
            'content' => "holdebates"
        ),
        array (
            'type' => 'html',
            'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img alt="RSS feed" border="0" align="middle" src="http://www.theyworkforyou.com/images/rss.gif"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of most recent debates</a></p>
</div>'
        )
    ));


}

$PAGE->page_end();
twfy_debug_timestamp("page end");
