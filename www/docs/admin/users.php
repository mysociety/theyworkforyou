<?php

include_once '../../includes/easyparliament/init.php';

$this_page = "admin_users";

$db = new ParlDB;

$message = '';
if ($email = get_http_var('scrub')) {
    $q = $db->query("DELETE FROM users WHERE email = :search", array(':search' => $email));
    $q = $db->query("DELETE FROM alerts WHERE email = :search", array(':search' => $email));
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

$user_data = array(
    'header' => array(
        'Name',
        'Email',
        'Confirmed?',
        'Registration time',
        'Delete',
    ),
    'rows' => array(),
);

$alert_data = array(
    'header' => array(
        'Email',
        'Created',
        'State',
        'Criteria',
    ),
    'rows' => array(),
);

if ($search = get_http_var('s')) {
    $q = $db->query("
        SELECT firstname, lastname, email, user_id, confirmed, registrationtime
        FROM users
        WHERE email LIKE :search OR firstname LIKE :search OR lastname LIKE :search",
        array(':search' => "%$search%"));

    $USERURL = new \MySociety\TheyWorkForYou\Url('userview');
    for ($row=0; $row<$q->rows(); $row++) {
        $USERURL->insert(array('u' => $q->field($row, 'user_id')));
        if ($q->field($row, 'confirmed') == 1) {
            $confirmed = 'Yes';
            $name = '<a href="' . $USERURL->generate() . '">' . _htmlspecialchars($q->field($row, 'firstname'))
                . ' ' . _htmlspecialchars($q->field($row, 'lastname')) . '</a>';
        } else {
            $confirmed = 'No';
            $name = _htmlspecialchars($q->field($row, 'firstname') . ' ' . $q->field($row, 'lastname'));
        }

        $user_data['rows'][] = array(
            $name,
            $email,
            $confirmed,
            $q->field($row, 'registrationtime'),
            scrub_form($q->field($row, 'email')),
        );
    }

    $q = $db->query("
        SELECT email, criteria, created, confirmed, deleted
        FROM alerts WHERE email = :search",
        array(':search' => $search));

    for ($row=0; $row<$q->rows(); $row++) {
        $confirmed = $q->field($row, 'confirmed');
        $deleted = $q->field($row, 'deleted');
        if ($deleted == 2) {
            $state = 'Suspended';
        } elseif ($deleted == 1) {
            $state = 'Deleted';
        } elseif ($confirmed) {
            $state = 'Confirmed';
        } else {
            $state = 'Unconfirmed';
        }

        $alert_data['rows'][] = array(
            $q->field($row, 'email'),
            $q->field($row, 'created'),
            $state,
            $q->field($row, 'criteria'),
        );
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

$PAGE->block_start(array('title'=>'Users'));
$PAGE->display_table($user_data);
$PAGE->block_end();

if (count($alert_data['rows'])) {
    $PAGE->block_start(array('title'=>'Alerts'));
    print scrub_form($search);
    $PAGE->display_table($alert_data);
    $PAGE->block_end();
}

$menu = $PAGE->admin_menu();
$PAGE->stripe_end(array(
    array(
        'type' => 'html',
        'content' => $menu,
    )
));

$PAGE->page_end();
