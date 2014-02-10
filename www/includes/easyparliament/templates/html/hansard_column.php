<?php
// For displaying the main Hansard content listings (by date).

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of hansard_gid for information about its structure and contents...

global $PAGE, $DATA, $this_page, $hansardmajors;

twfy_debug("TEMPLATE", "hansard_column.php");

$PAGE->page_start();

$PAGE->stripe_start();

print_r($data);
if (isset ($data)) {

    $prevlevel = '';

    print "\t\t\t\t<ul id=\"hansard-day\" class=\"hansard-day\">\n";

    // Cycle through each row...
    for ($i=0; $i<count($data); $i++) {
        $row = $data[$i];

        // Start a top-level item, eg a section.
        if ($row['htype'] == '10') {

            if ($prevlevel == 'sub') {
                print "\t\t\t\t\t</ul>\n\t\t\t\t</li>\n";

            } elseif ($prevlevel == 'top') {
                print "</li>\n";
            }

            print "\t\t\t\t<li>";

        // Start a sub-level item, eg a subsection.
        } else {

            if ($prevlevel == '') {
                print "\t\t\t\t<li>\n";

            } elseif ($prevlevel == 'top') {
                print "\n\t\t\t\t\t<ul>\n";
            }
            print "\t\t\t\t\t<li>";
        }


        // Are we going to make this (sub)section a link
        // and does it contain printable speeches?
        if ($row['htype'] == '10' && isset($row['excerpt']) && strstr($row['excerpt'], "was asked&#8212;")) {
            // We fake it here. We hope this section only has a single line like
            // "The Secretary of State was asked-" and we don't want to make it a link.
            $has_content = false;

        } elseif (isset($row['contentcount']) && $row['contentcount'] > 0) {
            $has_content = true;
        } else {
            $has_content = false;
        }


        if ($has_content) {

            print '<a href="' . $row['listurl'] . '"><strong>' . $row['body'] . '</strong></a> ';

            // For the "x speeches, x comments" text.
            $moreinfo = array();

            if ($hansardmajors[$row['major']]['type'] != 'other') {
                // All wrans have 2 speeches, so no need for this.
                // All WMS have 1 speech
                $plural = $row['contentcount'] == 1 ? 'speech' : 'speeches';
                $moreinfo[] = $row['contentcount'] . " $plural";
            }

            if ($row['totalcomments'] > 0) {
                $plural = $row['totalcomments'] == 1 ? 'annotation' : 'annotations';
                $moreinfo[] = $row['totalcomments'] . " $plural";
            }

            if (count($moreinfo) > 0) {
                print "<small>(" . implode (', ', $moreinfo) . ") </small>";
            }

        } else {
            // Nothing in this item, so no link.
            print '<strong>' . $row['body'] . '</strong>';
        }


        if (isset($row['excerpt'])) {
            print "<br>\n\t\t\t\t\t<span class=\"excerpt-debates\">" . trim_characters($row['excerpt'], 0, 200) . "</span>";
        }


        // End a top-level item.
        if ($row['htype'] == '10') {
            $prevlevel = 'top';

        // End a sub-level item.
        } else {
            print "</li>\n";
            $prevlevel = 'sub';
        }


    } // End cycling through rows.

    if ($prevlevel == 'sub') {
        // Finish final sub-level list.
        print "\t\t\t\t\t</ul>\n\t\t\t\t\t</li>\n";
    }

    print "\n\t\t\t\t</ul> <!-- end hansard-day -->\n";

} // End display of rows.

else {

    ?>
<p>No data to display.</p>
<?php
}

$sidebar = $hansardmajors[$this->major]['sidebar'];

$PAGE->stripe_end(array(
    array (
        'type' 	=> 'nextprev'
    ),
    array (
        'type' => 'include',
        'content' => 'calendar_'.$sidebar
    ),
    array (
        'type' => 'include',
        'content' => 'minisurvey'
    ),
    array (
        'type'	=> 'include',
        'content'	=> $sidebar
    )
));

?>
