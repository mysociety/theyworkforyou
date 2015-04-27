<?php

/**
 * Action recording for Qual 2
 */

include_once '../../includes/easyparliament/init.php';

function recordEvent($bucket, $event, $data = null, $timer = null) {

    // Hook up to the DB
    $db = new \ParlDB;

    $db->query('
        INSERT INTO `research_qual2_log`
        (`time`, `page`, `bucket`, `event`, `data`, `timer`)
        VALUES
        (:timestamp, :page, :bucket, :event, :data, :timer)
    ', array(
        'timestamp' => time(),
        'page' => $_POST['page'],
        'bucket' => (int) $bucket,
        'event' => $event,
        'data' => $data,
        'timer' => (int) $timer
    ));
}

// We must always have a page, bucket and event.
if (!isset($_POST['page']) OR !isset($_POST['bucket']) OR !isset($_POST['method'])) {

    $response_code = 400;
    $response = array(
        'success' => false,
        'message' => 'You must include a page, bucket and method.'
    );

} else {

    if (isset($_POST['data'])) {
        $dataPayload = $_POST['data'];
    } else {
        $dataPayload = null;
    }

    if (isset($_POST['timer'])) {
        $timer = (int) $_POST['timer'];
    } else {
        $timer = null;
    }

    switch ($_POST['method']) {

        // Valid for all buckets
        case 'view':

            recordEvent($_POST['bucket'], 'view');
            break;

        // Only ever in Bucket 1
        case 'show_popup':

            recordEvent(1, 'show_popup', $dataPayload, $timer);

            break;

        // Only ever in Bucket 1
        case 'surpressed_popup':

            recordEvent(1, 'surpressed_popup', $dataPayload, $timer);

            break;

        // Only ever in Bucket 1
        case 'click_popup_link':

            recordEvent(1, 'click_popup_link', $dataPayload, $timer);

            break;

        // Only ever in Bucket 2
        case 'click_nav_link':

            recordEvent(2, 'click_nav_link', $dataPayload, $timer);

            break;

        default:

            // No idea what's being attempted.
            $response_code = 400;
            $response = array(
                'success' => false,
                'message' => '"' . htmlspecialchars($_POST['method']) . '" is not a valid method.'
            );

            // Get us out of here, we know this has gone wrong.
            return;

    }

    // Method is valid, we've done a thing, send a reply.

    $response = array(
        'success' => true
    );
}

if (isset($response_code)) {
    http_response_code($response_code);
}

header("Cache-Control: no-cache, must-revalidate");
header('Content-Type: application/json');

echo json_encode($response);
