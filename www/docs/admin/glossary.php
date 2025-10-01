<?php

// Some sketchy crap for displaying pending glossary additions

include_once '../../includes/easyparliament/init.php';
include_once(INCLUDESPATH . "easyparliament/glossary.php");

$this_page = "admin_glossary";

$EDITQUEUE = new \MySociety\TheyWorkForYou\GlossaryEditQueue();

$args =  [
    'sort' => "regexp_replace",
];

$GLOSSARY = new GLOSSARY($args);

// If we're coming back here from a recent action we will have
// an http POST var of 'approve' or 'decline'.
// 'approve' can be an array or a single value depending on whether or not it was a form submission.
// 'decline' will always be a single value.
if (get_http_var('approve')) {
    $approve = get_http_var('approve', '', true);
    if (!is_array($approve)) {
        $approve =  [ $approve ];
    }
    // Add all approved items
    $data =  [
        'approvals' => $approve,
        'epobject_type' => 2,
    ];
    $EDITQUEUE->approve($data);
} elseif (get_http_var('decline')) {
    $decline =  [get_http_var('decline')];
    // Dump all declined items
    $data =  [
        'declines' => $decline,
        'epobject_type' => 2,
    ];
    $EDITQUEUE->decline($data);
} elseif (get_http_var('delete_confirm')) {
    $delete_id = get_http_var('delete_confirm');
    // Delete the existing glossary entry
    $GLOSSARY->delete($delete_id);
} elseif (get_http_var('bulk_download')) {
    $data = [];
    if (isset($GLOSSARY->terms)) {
        foreach ($GLOSSARY->terms as $term) {
            $data[] = [
                'glossary_id' => $term['glossary_id'],
                'title' => $term['title'],
                'body' => $term['body'],
            ];
        }
    }

    header('Content-Type: application/json');
    print(json_encode($data));
    exit();
} elseif (get_http_var('bulk_upload')) {
    $json = get_http_var('glossary_entries');
    $data = json_decode($json);

    $upload_success = true;
    $upload_errors = [];
    foreach ($data as $entry) {
        if (!isset($GLOSSARY->terms[$entry->glossary_id])) {
            $upload_errors[] = ['title' => $entry->title, 'error' => 'No entry with matching id'];
        } else {
            $update = ['glossary_id' => $entry->glossary_id, 'body' => $entry->body];
            $update = $GLOSSARY->update($update);
            if (isset($update['error'])) {
                $upload_errors[] = ['title' => $entry->title, 'error' => $update['error']];
            }
        }
    }
    if ($upload_errors) {
        $upload_success = false;
    }
}

$PAGE->page_start();

$PAGE->stripe_start();

if (get_http_var('bulk_upload')) {
    if ($upload_success) {
        print('<p>Upload successful!</p>');
    } else {
        print('<h4>There were errors in the upload</h4>');
        print('<ul>');
        foreach ($upload_errors as $error) {
            print('<li>' . $error['title'] . ': ' . $error['error'] . '</li>');
        }
        print('</ul>');
    }
}
$URL = new \MySociety\TheyWorkForYou\Url('admin_glossary');
$URL->insert(["bulk_download" => 1]);
?>
<h4>Bulk update</h4>
<p>
<form method="POST">
<input type="hidden" name="bulk_upload" value="1">
<label for="glossary_entries">JSON (existing entries only)</label>
<textarea name="glossary_entries" id="glossary_entries"><?php if (isset($upload_success) && !$upload_success) {
    print($json);
} ?></textarea>
<input type="submit" value="Update">
</form>
</p>

<p>
<a href="<?php echo $URL->generate('url') ?>">Download all entries as JSON</a>
</p>

<h4>Entries</h4>
<?php

// Display the results
if (isset($GLOSSARY->terms)) {

    foreach ($GLOSSARY->terms as $term) {
        $GLOSSARY->current_term = $term;
        $PAGE->glossary_display_term($GLOSSARY);
    }
}

// Now that's easy :)
// Even easier when you copy it :p

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'		=> 'html',
        'content'	=> $menu,
    ],
]);

$PAGE->page_end();
