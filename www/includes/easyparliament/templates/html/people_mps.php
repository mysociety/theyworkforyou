<?php

/*
Used on the 'All MPs' page to produce the list of MPs.

$data = array (
	'info' => array (
		'order' => 'first_name'
	),
	'data' => array (
		'first_name'	=> 'Fred',
		'last_name'		=> 'Bloggs,
		'person_id'		=> 23,
		'constituency'	=> 'Here',
		'party'			=> 'Them'
	)
);
*/

global $this_page;

twfy_debug("TEMPLATE", "people_mps.php");

$order = $data['info']['order'];

$URL = new URL($this_page);

if ($order == 'first_name') {
	$th_first_name = 'First Name |';
} else {
	$URL->insert(array('o'=>'f'));
	$th_first_name = '<a href="'. $URL->generate() .'">First name</a> |';
}

if ($order == 'last_name') {
	$th_last_name = 'Last name |';
} else {
	$URL->insert(array('o'=>'l'));
	$th_last_name = '<a href="' . $URL->generate() . '">Last name</a> |';
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

?>
				<div class="sort">
				    Sort by:
				    <ul>
                        <li><?php echo $th_last_name; ?></li>                        				        
                        <li><?php echo $th_first_name; ?></li>
                        <li><?php echo $th_party; ?></li>
                        <?php	if ($order == 'expenses') { ?>
                        	<li>2004 Expenses Grand Total</li>
                        <?php	} elseif ($order == 'debates') { ?>
                        	<li>Debates spoken in the last year</li>
                        <?php	} elseif ($order == 'safety') { ?>
                        	<li>Swing to lose seat (%)</li>
                        <?php	}
                        ?>
				    </ul>
				</div>
				<table class="people">
				<thead>
                <tr>
				    <th colspan="2">Name</th>
				    <th>Party</th>
				    <th>Constituency</th>
				    <th>Positions</th>				    
				    <?php	if ($order == 'expenses') { ?>
                    	<th>2004 Expenses Grand Total</th>
                    <?php	} elseif ($order == 'debates') { ?>
                    	<th>Debates spoken in the last year</th>
                    <?php	} elseif ($order == 'safety') { ?>
                    	<th>Swing to lose seat (%)</th>
                    <?php	}
                    ?>
                </tr>
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

	// Stripes
	$style = $style == '1' ? '2' : '1';

	$name = member_full_name(1, $mp['title'], $mp['first_name'], $mp['last_name'], $mp['constituency']);
	
#	$MPURL->insert(array('pid'=>$mp['person_id']));
	?>
				<tr>
                <td class="row">
                <?php
                list($image,$sz) = find_rep_image($mp['person_id'], true, true);
                if ($image) {
                    echo '<a href="' . $MPURL->generate().make_member_url($mp['first_name'].' '.$mp['last_name'], $mp['constituency'], 1) . '" class="speakerimage"><img height="59" alt="" src="', $image, '"';
                    echo '></a>';
                }
                ?>
                </td>
				<td class="row-<?php echo $style; ?>"><a href="<?php echo $MPURL->generate().make_member_url($mp['first_name'].' '.$mp['last_name'], $mp['constituency'], 1); ?>"><?php echo $name; ?></a>
<?
if ($mp['left_reason'] == 'general_election_not_standing') {
    print '<br><em>Standing down</em>';
}
?></td>
				<td class="row-<?php echo $style; ?>"><?php echo $mp['party']; ?></td>
				<td class="row-<?php echo $style; ?>"><?php echo $mp['constituency']; ?></td>
				<td class="row-<?php echo $style; ?>"><?php
	if (is_array($mp['pos'])) print join('<br>', array_map('prettify_office', $mp['pos'], $mp['dept']));
	elseif ($mp['pos'] || $mp['dept']) print prettify_office($mp['pos'], $mp['dept']);
	else print '&nbsp;';
?></td>
<?php	if ($order == 'expenses') { ?>
				<td class="row-<?php echo $style; ?>">&pound;<?php echo number_format($mp['data_value']); ?></td>
<?php	} elseif ($order == 'debates') { ?>
				<td class="row-<?php echo $style; ?>"><?php echo number_format($mp['data_value']); ?></td>
<?php	} elseif ($order == 'safety') { ?>
				<td class="row-<?php echo $style; ?>"><?=$mp['data_value'] ?></td>
<?php	}
?>
				</tr>
<?php

}

?>
