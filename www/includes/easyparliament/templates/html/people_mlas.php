<?php

# Used on the 'All MLAs' page to produce the list of MLAs.

global $this_page;

twfy_debug("TEMPLATE", "people_mlas.php");

$order = $data['info']['order'];

$URL = new URL($this_page);

if ($order == 'first_name') {
	$th_name = 'First';
} else {
	$URL->insert(array('o'=>'f'));
	$th_name = '<a href="'. $URL->generate() .'">First</a>';
}
$th_name .= ' &amp; ';
if ($order == 'last_name') {
	$th_name .= 'last';
} else {
	$URL->insert(array('o'=>'l'));
	$th_name .= '<a href="' . $URL->generate() . '">Last</a>';
}
$th_name .= ' name';
$URL->insert(array('o'=>'p'));
$th_party = '<a href="' . $URL->generate() . '">Party</a>';
$URL->insert(array('o'=>'c'));
$th_constituency = '<a href="' . $URL->generate() . '">Constituency</a>';

if ($order == 'party') {
	$th_party = 'Party';
} elseif ($order == 'constituency') {
	$th_constituency = 'Constituency';
}

?>
<table border="0" cellpadding="4" cellspacing="0" width="90%" class="people">
<thead>
<th><?php echo $th_name; ?></th>
<th><?php echo $th_party; ?></th>
<th><?php echo $th_constituency; ?></th>
</thead>
<tbody>
<?php

$MPURL = new URL(str_replace('s', '', $this_page));
$style = '2';
foreach ($data['data'] as $pid => $mp) {
	render_mps_row($mp, $style, $order, $MPURL);
}
?>
</tbody>
</table>
<?

function render_mps_row($mp, &$style, $order, $MPURL) {
	$style = $style == '1' ? '2' : '1';
	$name = member_full_name(3, $mp['title'], $mp['first_name'], $mp['last_name'], $mp['constituency']);
	?>
<tr>
<td class="row-<?php echo $style; ?>"><a href="<?php
	echo $MPURL->generate().make_member_url($mp['first_name'].' '.$mp['last_name'], $mp['constituency'], 3);
?>"><?php echo $name; ?></a></td>
<td class="row-<?php echo $style; ?>"><?php echo $mp['party']; ?></td>
<td class="row-<?php echo $style; ?>"><?php echo $mp['constituency']; ?></td>
</tr>
<?php

}

?>
