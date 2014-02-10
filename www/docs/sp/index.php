<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";

// For displaying all the SP debates on a day, or a single debate.

if (get_http_var("d") != "") {
    $this_page = "spdebatesday";
    $args = array (
        'date' => get_http_var('d')
    );
    $LIST = new SPLIST;
    $LIST->display('date', $args);

} elseif (get_http_var('id') != "") {
    $this_page = "spdebates";
    $args = array (
        'gid' => get_http_var('id'),
        's'	=> get_http_var('s'),	// Search terms to be highlighted.
        'member_id' => get_http_var('m'),	// Member's speeches to be highlighted.
        'glossarise' => 1	// Glossary is on by default
    );

    if (preg_match('/speaker:(\d+)/', get_http_var('s'), $mmm))
        $args['person_id'] = $mmm[1];

    // Glossary can be turned off in the url
    if (get_http_var('ug') == 1) {
        $args['glossarise'] = 0;
    }
    else {
        $args['sort'] = "regexp_replace";
        $GLOSSARY = new GLOSSARY($args);
    }

    $LIST = new SPLIST;

    $result = $LIST->display('gid', $args);
    // If it is a redirect, change URL
    if (is_string($result)) {
        $URL = new URL('spdebates');
        $URL->insert( array('id'=>$result) );
        header('Location: http://' . DOMAIN . $URL->generate('none'), true, 301);
        exit;
    }

} elseif (get_http_var('y') != '') {

    $this_page = 'spdebatesyear';

    if (is_numeric(get_http_var('y'))) {
        $DATA->set_page_metadata($this_page, 'title', get_http_var('y'));
    }

    $PAGE->page_start();

    $PAGE->stripe_start();

    $args = array (
        'year' => get_http_var('y')
    );

    $LIST = new SPLIST;

    $LIST->display('calendar', $args);

    $PAGE->stripe_end(array(
        array (
            'type' => 'nextprev'
        ),
        array (
            'type' => 'include',
            'content' => "spdebates"
        )
    ));

} elseif (get_http_var('gid') != '') {
    $this_page = 'spdebate';
    $args = array('gid' => get_http_var('gid') );
    $LIST = new SPLIST;
    $result = $LIST->display('gid', $args);
    // If it is a redirect, change URL
    if (is_string($result)) {
        $URL = new URL('spdebate');
        $URL->insert( array('gid'=>$result) );
        header('Location: http://' . DOMAIN . $URL->generate('none'));
        exit;
    }
    if ($LIST->htype() == '12' || $LIST->htype() == '13') {
        $PAGE->stripe_start('side', 'comments');
        $COMMENTLIST = new COMMENTLIST;
        $args['user_id'] = get_http_var('u');
        $args['epobject_id'] = $LIST->epobject_id();
        $COMMENTLIST->display('ep', $args);
        $PAGE->stripe_end();
        $PAGE->stripe_start('side', 'addcomment');
        $commendata = array(
            'epobject_id' => $LIST->epobject_id(),
            'gid' => get_http_var('gid'),
            'return_page' => $this_page
        );
        $PAGE->comment_form($commendata);
        if ($THEUSER->isloggedin()) {
            $sidebar = array(
                array(
                    'type' => 'include',
                    'content' => 'comment'
                )
            );
            $PAGE->stripe_end($sidebar);
        } else {
            $PAGE->stripe_end();
        }
    }
} else {
    $this_page = "spdebatesfront";
    $PAGE->page_start();
    $PAGE->stripe_start();
    ?>
                <h2>Busiest debates from the most recent week</h2>
<?php

    $LIST = new SPLIST;
    $LIST->display('biggest_debates', array('days'=>7, 'num'=>20));

    $rssurl = $DATA->page_metadata($this_page, 'rss');
    $PAGE->stripe_end(array(
        array (
            'type' => 'nextprev'
        ),
        array (
            'type' => 'include',
            'content' => "spdebates"
        ),
        array (
            'type' => 'include',
            'content' => 'calendar_spdebates'
        ),
        array (
            'type' => 'html',
            'content' => '<div class="block"><h4><a href="/' . $rssurl . '">RSS feed of most recent debates</a></h4></div>'
        )
    ));

}

$PAGE->page_end();

twfy_debug_timestamp("page end");

?>
