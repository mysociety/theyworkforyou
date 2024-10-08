<?php

include_once '../../includes/easyparliament/init.php';
include_once(INCLUDESPATH . "easyparliament/commentreportlist.php");

$this_page = "admin_home";

$db = new ParlDB();

$PAGE->page_start();

$PAGE->stripe_start();

///////////////////////////////////////////////////////////////
// General stats.

$PAGE->block_start(['title' => 'Stats']);

$confirmedusers = $db->query("SELECT COUNT(*) AS count FROM users WHERE confirmed = '1'")->first()['count'];
$unconfirmedusers = $db->query("SELECT COUNT(*) AS count FROM users WHERE confirmed = '0'")->first()['count'];
$olddate = gmdate("Y-m-d H:i:s", time() - 86400);
$dayusers = $db->query("SELECT COUNT(*) AS count FROM users WHERE lastvisit > '$olddate'")->first()['count'];
$olddate = gmdate("Y-m-d H:i:s", time() - 86400 * 7);
$weekusers = $db->query("SELECT COUNT(*) AS count FROM users WHERE lastvisit > '$olddate'")->first()['count'];
?>
<ul>
<li>Confirmed users: <?php echo $confirmedusers; ?></li>
<li>Unconfirmed users: <?php echo $unconfirmedusers; ?></li>
<li>Logged-in users active in past day: <?php echo $dayusers; ?></li>
<li>Logged-in users active in past week: <?php echo $weekusers; ?></li>
</ul>

<?php
$PAGE->block_end();

///////////////////////////////////////////////////////////////
// Recent users.

?>
<h4>Recently registered users</h4>
<form action="./users.php" method="get">
    <label for="user_search">Search:</label>
    <input type="text" name="s" id="user_search">
    <input type="submit" value="Go">
</form>
<?php

$q = $db->query("SELECT firstname,
                        lastname,
                        email,
                        user_id,
                        confirmed,
                        registrationtime
                FROM	users
                ORDER BY registrationtime DESC
                LIMIT 50
                ");

$rows = [];
$USERURL = new \MySociety\TheyWorkForYou\Url('userview');

foreach ($q as $row) {
    $user_id = $row['user_id'];

    $USERURL->insert(['u' => $user_id]);

    if ($row['confirmed'] == 1) {
        $confirmed = 'Yes';
        $name = '<a href="' . $USERURL->generate() . '">' . _htmlspecialchars($row['firstname'])
            . ' ' . _htmlspecialchars($row['lastname']) . '</a>';
    } else {
        $confirmed = 'No';
        $name = _htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
    }

    $rows[] =  [
        $name,
        '<a href="mailto:' . $row['email'] . '">' . $row['email'] . '</a>',
        $confirmed,
        $row['registrationtime'],
    ];
}

$tabledata =  [
    'header' =>  [
        'Name',
        'Email',
        'Confirmed?',
        'Registration time',
    ],
    'rows' => $rows,
];

$PAGE->display_table($tabledata);

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'		=> 'html',
        'content'	=> $menu,
    ],
]);

$PAGE->page_end();

?>
