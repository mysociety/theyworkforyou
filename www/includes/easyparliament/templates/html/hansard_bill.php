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
	foreach ($data['rows'] as $row) {
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
		$has_content = true;
		
		if ($has_content) {
			echo '<a ';
			if ($row['htype'] == 10) {
				echo 'name="sitting', ($row['sitting']);
				if ($row['part'] > 0) echo "_$row[part]";
				echo '" ';
			}
			echo 'href="' . $row['listurl'] . '"><strong>' . $row['body'];
			if ($row['htype'] == 10) {
				$sitting = make_ranking($row['sitting']);
				echo ", $sitting sitting";
				if ($row['part'] > 0) echo ", part $row[part]";
			}
			echo '</strong></a> ';
			// For the "x speeches, x comments" text.
			$moreinfo = array();
			$plural = $row['contentcount'] == 1 ? 'speech' : 'speeches';
			$moreinfo[] = $row['contentcount'] . " $plural";
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
	$list = '<div class="block"> <h4>Committee Membership and attendance <small>(out of ' . $outof . ')</small></h4>';
	if (isset($data['info']['committee']['chairmen'])) {
		$list .= '<h5>Chair';
		if (count($data['info']['committee']['chairmen'])>1) $list .= 'men';
		else $list .= 'man';
		$list .= '</h5> <ul>';
		foreach ($data['info']['committee']['chairmen'] as $id => $member) {
			$list .= '<li><a href="/mp/?m=' . $id . '">' . $member['name'] . '</a>';
			$list .= ' <small>('.$member['attending'].')</small>';
		}
		$list .= '</ul>';
	}
	$list .= '<h5>Members</h5> <ul>';
	if (isset($data['info']['committee']['members'])) {
		foreach ($data['info']['committee']['members'] as $id => $member) {
			$list .= '<li><a href="/mp/?m=' . $id . '">' . $member['name'] . '</a>';
			$list .= ' <small>('.$member['attending'].')</small>';
		}
	} else {
		$list .= '<li>No members (presumably a failure in our parsing)</li>';
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
