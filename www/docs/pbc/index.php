<?php

include_once '../../includes/easyparliament/init.php';
include_once INCLUDESPATH . "easyparliament/glossary.php";

# For displaying Standing Committee debates. I know they're Public Bill
# Committees now, but I've called them standing committees everywhere

$bill = str_replace('_', ' ', get_http_var('bill'));
$session = get_http_var('session');
$id = get_http_var('id');

$bill_id = null;
if ($bill && $session) {
    $db = new ParlDB;
    $q = $db->query('select id,standingprefix from bills where title="' . mysql_real_escape_string($bill) . '"
        and session = "'.mysql_real_escape_string($session).'"');
    if ($q->rows()) {
        $bill_id = $q->field(0, 'id');
        $standingprefix = $q->field(0, 'standingprefix');
    }
}

$committee = new StandingCommittee($session, $bill);

if ($bill_id && !$id) {
    # Display the page for a particular bill
    $this_page = 'pbc_bill';
    $args = array (
        'id' => $bill_id,
        'title' => $bill,
        'session' => $session,
    );
    $committee->display('bill', $args);
} elseif ($bill_id && $id) {
    # Display the debate for a particular clause
    $this_page = 'pbc_clause';
    $args = array (
        'gid' => $standingprefix . $id,
        's'	=> get_http_var('s'),
        'member_id' => get_http_var('m'),
        'glossarise' => 1,
        'sort' => 'regexp_replace',
        'bill_id' => $bill_id,
        'bill_title' => $bill,
        'bill_session' => $session,
    );
    $GLOSSARY = new GLOSSARY($args); # Why a global?

    if (preg_match('/speaker:(\d+)/', get_http_var('s'), $mmm))
        $args['person_id'] = $mmm[1];

    $result = $committee->display('gid', $args);
    # If it is a redirect, change URL. Magically (as in I can't remember
    # quite why), pbc_clause will contain the new URL without any change...
    if (is_string($result)) {
        $URL = new URL('pbc_clause');
        $URL->remove(array('id'));
        header('Location: http://' . DOMAIN . $URL->generate('none'), true, 301);
        exit;
    }

    /* This section below is shared between here and everywhere else - factor it out! */
    if ($committee->htype() == '12' || $committee->htype() == '13') {
        $PAGE->stripe_start('side', 'comments');
        $COMMENTLIST = new COMMENTLIST;
        $args['user_id'] = get_http_var('u');
        $args['epobject_id'] = $committee->epobject_id();
        $COMMENTLIST->display('ep', $args);
        $PAGE->stripe_end();
        $PAGE->stripe_start('side', 'addcomment');
        $commendata = array(
            'epobject_id' => $committee->epobject_id(),
            'gid' => get_http_var('id'),
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
} elseif ($bill && !$bill_id && $session) {
    # Illegal bill title, redirect to session page
    $URL = new URL('pbc_session');
    header('Location: ' . $URL->generate() . urlencode($session));
    exit;
} elseif ($session) {
    # Display the bills for a particular session
    $this_page = 'pbc_session';
    $DATA->set_page_metadata($this_page, 'title', "Session $session");
    $args = array (
        'session' => $session,
    );
    $committee->display('session', $args);
} else {
    # Front page, display the most recent data
    $this_page = "pbc_front";
    $PAGE->page_start();
    $PAGE->stripe_start();
    ?>
<h2>Most recent Public Bill committee debates</h2>
<p><a href="/pbc/2013-14/">See all committees for the current session</a></p>
<?php

    $committee->display( 'recent_pbc_debates', array( 'num' => 50 ) );
    $rssurl = $DATA->page_metadata($this_page, 'rss');
    $PAGE->stripe_end(array(
        array (
            'type' => 'include',
            'content' => "minisurvey"
        ),
        array(
            'type' => 'html',
            'content' => '
<div class="block">
<h4>Search bill committees</h4>
<div class="blockbody">
<form action="/search/" method="get">
<p><input type="text" name="q" id="search_input" value="" size="40"> <input type="submit" value="Go">
<input type="hidden" name="section" value="pbc">
</p>
</form>
</div>
</div>
',
    ),
        array (
            'type' => 'include',
            'content' => "pbc"
        ),
        array (
            'type' => 'html',
            'content' => '<div class="block">
<h4>RSS feed</h4>
<p><a href="' . WEBPATH . $rssurl . '"><img alt="RSS feed" border="0" align="middle" src="http://www.theyworkforyou.com/images/rss.gif"></a>
<a href="' . WEBPATH . $rssurl . '">RSS feed of most recent committee debates</a></p>
</div>'
        )
    ));

}

$PAGE->page_end();
