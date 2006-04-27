<?php
# $Id: constituencies.inc,v 1.1 2006-04-27 14:20:39 twfy-live Exp $

function normalise_constituency_name($name) {
    $db = new ParlDB;

    // Use &amp; everywhere, as in DB
    $name = str_replace("&amp;", "&", $name);
    $name = str_replace("&", "&amp;", $name);

    $query = "select cons_id from constituency where name like '" . mysql_escape_string($name) . "' and from_date <= date(now()) and date(now()) <= to_date";
    $q1 = $db->query($query);
    if ($q1->rows <= 0)
        return false;

    $query = "select name from constituency where main_name and cons_id = '".$q1->field(0,'cons_id')."'";
    $q2 = $db->query($query);
    if ($q2->rows <= 0)
        return false;

    return $q2->field(0, "name");
}

?>
