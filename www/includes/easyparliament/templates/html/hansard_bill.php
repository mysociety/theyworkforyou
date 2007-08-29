<?php
// For displaying the main Hansard content listings (by date).

// Remember, we are currently within the DEBATELIST or WRANSLISTS class,
// in the render() function.

// The array $data will be packed full of luverly stuff about hansard objects.
// See the bottom of hansard_gid for information about its structure and contents...

global $PAGE, $DATA, $this_page, $hansardmajors;

twfy_debug("TEMPLATE", "hansard_bill.php");

$PAGE->page_start();
$PAGE->stripe_start();

if (isset ($data['rows'])) {
	$prevlevel = '';
	print "\t\t\t\t<ul id=\"hansard-day\">\n";
	// Cycle through each row...
	for ($i=0; $i<count($data['rows']); $i++) {
		$row = $data['rows'][$i];
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
		} elseif ($row['htype'] == '11' && $hansardmajors[$row['major']]['type'] == 'other') {
			$has_content = true;
		} else {
			$has_content = true; # XXX
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
				$plural = $row['totalcomments'] == 1 ? 'comment' : 'comments';
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

$list = '';
if (isset($data['info']['committee'])) {
	$outof = $data['info']['committee']['sittings'];
	$list = '<div class="block"> <h4>Committee Membership and attendance <small>(out of '
		. $outof . ')</small></h4> <h5>Chair';
	if (count($data['info']['committee']['chairmen'])>1) $list .= 'men';
	else $list .= 'man';
	$list .= '</h5> <ul>';
	foreach ($data['info']['committee']['chairmen'] as $id => $member) {
		$list .= '<li><a href="/mp/?m=' . $id . '">' . $member['name'] . '</a>';
		$list .= ' <small>('.$member['attending'].')</small>';
	}
	$list .= '</ul> <h5>Members</h5> <ul>';
	foreach ($data['info']['committee']['members'] as $id => $member) {
		$list .= '<li><a href="/mp/?m=' . $id . '">' . $member['name'] . '</a>';
		$list .= ' <small>('.$member['attending'].')</small>';
	}
	$list .= '</ul>
<p>[ Committee memberships can change partway through. ]</p>
</div>';
}

$sidebar = $hansardmajors[$this->major]['sidebar'];

$PAGE->stripe_end(array(
	array (
		'type' 	=> 'html',
		'content' => '<p align="center"><a href="./">All Bills in this session</a></p>'
	),
	array (
		'type' => 'html',
		'content' => $list,
	),
	array (
		'type'	=> 'include',
		'content' => $sidebar
	)
));


?>
