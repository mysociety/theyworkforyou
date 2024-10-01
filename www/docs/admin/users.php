<?php

include_once '../../includes/easyparliament/init.php';

$this_page = "admin_users";

$db = new ParlDB();

$message = '';
if ($email = get_http_var('scrub')) {
    $q = $db->query("DELETE FROM users WHERE email = :search", [':search' => $email]);
    $q = $db->query("DELETE FROM alerts WHERE email = :search", [':search' => $email]);
    $message = "That user and alerts have been deleted.";
}

function scrub_form($email) {
    $email = _htmlspecialchars($email);
    return <<<EOF
        <form method="post" onsubmit="return confirm('Are you sure?');">
            <input type="hidden" name="scrub" value="$email">
            <input type="submit" value="Delete user">
        </form>
        EOF;
}

$user_data = [
    'header' => [
        'Name',
        'Email',
        'Confirmed?',
        'Registration time',
        'Delete',
    ],
    'rows' => [],
];

$alert_data = [
    'header' => [
        'Email',
        'Created',
        'State',
        'Criteria',
    ],
    'rows' => [],
];

if ($search = get_http_var('s')) {
    $q = $db->query(
        "
        SELECT firstname, lastname, email, user_id, confirmed, registrationtime
        FROM users
        WHERE email LIKE :search OR firstname LIKE :search OR lastname LIKE :search",
        [':search' => "%$search%"]
    );

    $USERURL = new \MySociety\TheyWorkForYou\Url('userview');
    foreach ($q as $row) {
        $USERURL->insert(['u' => $row['user_id']]);
        if ($row['confirmed'] == 1) {
            $confirmed = 'Yes';
            $name = '<a href="' . $USERURL->generate() . '">' . _htmlspecialchars($row['firstname'])
                . ' ' . _htmlspecialchars($row['lastname']) . '</a>';
        } else {
            $confirmed = 'No';
            $name = _htmlspecialchars($row['firstname'] . ' ' . $row['lastname']);
        }

        $user_data['rows'][] = [
            $name,
            $email,
            $confirmed,
            $row['registrationtime'],
            scrub_form($row['email']),
        ];
    }

    $q = $db->query(
        "
        SELECT email, criteria, created, confirmed, deleted
        FROM alerts WHERE email = :search",
        [':search' => $search]
    );

    foreach ($q as $row) {
        $confirmed = $row['confirmed'];
        $deleted = $row['deleted'];
        if ($deleted == 2) {
            $state = 'Suspended';
        } elseif ($deleted == 1) {
            $state = 'Deleted';
        } elseif ($confirmed) {
            $state = 'Confirmed';
        } else {
            $state = 'Unconfirmed';
        }

        $alert_data['rows'][] = [
            $row['email'],
            $row['created'],
            $state,
            $row['criteria'],
        ];
    }
}

$PAGE->page_start();
$PAGE->stripe_start();

if ($message) {
    print "<p class='alert-box'>$message</p>";
}

?>
<form action="./users.php" method="get">
    <label for="user_search">Search:</label>
    <input type="text" name="s" id="user_search" value="<?=_htmlspecialchars($search) ?>">
    <input type="submit" value="Go">
</form>
<?php

$PAGE->block_start(['title' => 'Users']);
$PAGE->display_table($user_data);
$PAGE->block_end();

if (count($alert_data['rows'])) {
    $PAGE->block_start(['title' => 'Alerts']);
    print scrub_form($search);
    $PAGE->display_table($alert_data);
    $PAGE->block_end();
}

$menu = $PAGE->admin_menu();
$PAGE->stripe_end([
    [
        'type' => 'html',
        'content' => $menu,
    ],
]);

$PAGE->page_end();
