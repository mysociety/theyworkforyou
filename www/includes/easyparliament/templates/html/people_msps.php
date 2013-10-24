<?php

# Used on the 'All MLAs' page to produce the list of MLAs.

global $this_page;

twfy_debug("TEMPLATE", "people_msps.php");

$order = $data['info']['order'];

$URL = new URL($this_page);

if ($order == 'first_name') {
	$th_first_name = 'First name';
} else {
	$URL->insert(array('o'=>'f'));
	$th_first_name = '<a href="'. $URL->generate() .'">First name</a>';
}

if ($order == 'last_name') {
	$th_last_name = 'Last name';
} else {
	$URL->insert(array('o'=>'l'));
	$th_last_name = '<a href="' . $URL->generate() . '">Last name</a>';
}

$URL->insert(array('o'=>'p'));
$th_party = '<a href="' . $URL->generate() . '">Party</a>';
$URL->insert(array('o'=>'c'));
$th_constituency = '<a href="' . $URL->generate() . '">Constituency</a>';

if ($order == 'party') {
	$th_party = 'Party';
} elseif ($order == 'constituency') {
	$th_constituency = 'Constituency';
}

if (!count($data['data'])) {
?>

<p>There are currently no MSPs &ndash; we are presumably in the period between the
dissolution of the Parliament and its next election.</p>

<?
} else {

$MPURL = new URL('yourmp');
global $THEUSER;
$pc_form = true;
if ($THEUSER->isloggedin() && $THEUSER->postcode() != '' || $THEUSER->postcode_is_set()) {
	// User is logged in and has a postcode, or not logged in with a cookied postcode.

	// (We don't allow the user to search for a postcode if they
	// already have one set in their prefs.)

	$MEMBER = new MEMBER(array ('postcode'=>$THEUSER->postcode(), 'house'=>1));
	if ($MEMBER->valid) {
		$pc_form = false;
		if ($THEUSER->isloggedin()) {
			$CHANGEURL = new URL('useredit');
		} else {
			$CHANGEURL = new URL('userchangepc');
		}
		$mpname = $MEMBER->first_name() . ' ' . $MEMBER->last_name();
		$former = "";
		$left_house = $MEMBER->left_house();
		if ($left_house[1]['date'] != '9999-12-31') {
			$former = 'former';
		}
?>
<p><a href="<?php echo $MPURL->generate(); ?>"><strong>Find out about <?php echo $mpname; ?>, your <?= $former ?> MSP</strong></a><br>
In <?php echo strtoupper(htmlentities($THEUSER->postcode())); ?> (<a href="<?php echo $CHANGEURL->generate(); ?>">Change your postcode</a>)</p>
<?php
	}
}

if ($pc_form) { ?>
	<form action="/postcode/" method="get">
	<p><strong>Looking for your <acronym title="Members of the Scottish Parliament">MSP</acronym>, <acronym title="Member of Parliament">MP</acronym> or
	<acronym title="Members of the (Northern Irish) Legislative Assembly">MLA</acronym>?</strong><br>
	<label for="pc">Enter your UK postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo htmlentities($THEUSER->postcode()); ?>" class="text">&nbsp;&nbsp;<input type="submit" value=" Go " class="submit"></p>
	</form>
<?php
}

?>

<div class="sort">
    Sort by:
    <ul>
        <li><?php echo $th_last_name; ?> |</li>                
        <li><?php echo $th_first_name; ?> |</li>
        <li><?php echo $th_party; ?></li>
    </ul>
</div>

<table class="people">
<thead>
<tr>
<th colspan="2">Name</th>
<th>Party</th>
<th>Constituency</th>
</tr>
</thead>
<tbody>
<?php
    $MPURL = new URL(substr($this_page, 0, -1));
    $style = '2';
    foreach ($data['data'] as $pid => $mp) {
	    render_mps_row($mp, $style, $order, $MPURL);
    }
?>
</tbody>
</table>
<?

}

function render_mps_row($mp, &$style, $order, $MPURL) {
	$style = $style == '1' ? '2' : '1';
	$name = member_full_name(4, $mp['title'], $mp['first_name'], $mp['last_name'], $mp['constituency']);
	?>
<tr>
    <td class="row">
    <?php
    list($image,$sz) = find_rep_image($mp['person_id'], true, true);
    if ($image) {
        echo '<a href="' . $MPURL->generate().make_member_url($mp['first_name'].' '.$mp['last_name'], NULL, 4, $mp['person_id']) . '" class="speakerimage"><img height="59" alt="" src="', $image, '"';
        echo '></a>';
    }
    ?>
    </td>
<td class="row-<?php echo $style; ?>"><a href="<?php
	echo $MPURL->generate().make_member_url($mp['first_name'].' '.$mp['last_name'], NULL, 4, $mp['person_id']);
?>"><?php echo $name; ?></a></td>
<td class="row-<?php echo $style; ?>"><?php echo $mp['party']; ?></td>
<td class="row-<?php echo $style; ?>"><?php echo $mp['constituency']; ?></td>
</tr>
<?php

}

?>
