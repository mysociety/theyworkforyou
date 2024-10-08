<?php

include_once '../../includes/easyparliament/init.php';
$this_page = 'admin_wikipedia';
$db = new ParlDB();

$PAGE->page_start();
$PAGE->stripe_start();
remove_form();
list_ignored();
$PAGE->stripe_end([
    [ 'type' => 'html', 'content' => $PAGE->admin_menu() ],
]);
$PAGE->page_end();

function remove_form() {
    global $db;
    if ($title = get_http_var('title')) {
        $title = str_replace(' ', '_', $title);
        $db->query('INSERT INTO titles_ignored (title) VALUES (:title)', [':title' => $title]);
        print '<p>Ignored!</p>';
    }
    ?>
<form method="post">
Title: <input type="text" name="title" value="">
<input type="submit" value="Remove">
</form>
<?php
}

function list_ignored() {
    global $db;
    $q = $db->query('SELECT title FROM titles_ignored');
    print '<h2>Currently ignored</h2> <ul>';
    foreach ($q as $r) {
        print '<li>' . $r['title'];
    }
    print '</ul>';
}
