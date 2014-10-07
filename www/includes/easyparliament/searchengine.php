<?php

include_once INCLUDESPATH . 'dbtypes.php';

if (defined('XAPIANDB') AND XAPIANDB != '') {
    if (file_exists('/usr/share/php/xapian.php')) {
        include_once '/usr/share/php/xapian.php';
    } else {
        twfy_debug('SEARCH', '/usr/share/php/xapian.php does not exist');
    }
}

global $SEARCHENGINE;
$SEARCHENGINE = null;
