<?php
/*
 * For displaying day of MP stuff for matching against playing video
 */

global $want; # XXX

twfy_debug("TEMPLATE", "hansard_search_video.php");

?>

<style type="text/css">
.hi { background-color: #ffff33; }
</style>

<p style="font-size:larger;background-color:#ccccff;padding:6px;">Okay, here
are the search results for that for the current day. Hopefully you recognise
one of them!</p>

<?php

if (isset ($data['rows']) && count($data['rows']) > 0) {
    echo '<dl id="searchresults">';
    foreach ($data['rows'] as $n => $row) {
        echo '<dt>';
        if (isset($row['parent']) && count($row['parent']) > 0) {
            echo ('<strong>' . $row['parent']['body'] . '</strong>');
        }
        echo '</dt> <dd>';
        if (isset($row['speaker']) && count($row['speaker'])) {
            $sp = $row['speaker'];
            echo "<em>" . ucfirst(member_full_name($sp['house'], $sp['title'], $sp['first_name'], $sp['last_name'], $sp['constituency'])) . "</em>: ";
        }

        echo '&#8220;' . $row['extract'] . "&#8221;</dd>\n";
        echo '<dd><em><small>This speech is ';
        if ($row['hpos']-$want['hpos']>0) {
            echo ($row['hpos']-$want['hpos']) . ' later than';
        } else {
            echo ($want['hpos']-$row['hpos']) . ' earlier than';
        }
        echo ' the one you want &ndash; ';
        if ($row['video_status']&8 || !($row['video_status']&1)) {
            echo ' it doesn\'t have any video';
        } elseif ($row['video_status']&4) {
            echo ' it has already been matched';
        } elseif ($row['video_status']&1) {
            # Only for Commons currently
            echo ' if you prefer, <a target="_top" onclick="t = parent.document[\'video\'].currentTime(); this.href += t;" href="/video/?gid=debate/' . $row['gid'] . '&amp;file=' . $want['file'] . '&amp;start=">switch to matching this speech instead</a>';
        }
        echo '</small></em></dd>';
    }
    echo '</dl>';
} else {
    echo '<p>No data to display.</p>';
}
