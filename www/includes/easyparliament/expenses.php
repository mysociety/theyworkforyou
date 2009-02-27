<?
# Expenses related functions

function expenses_display_table($extra_info) {
	$out = '<p class="italic">Figures in brackets are ranks. Parliament\'s <a href="http://www.parliament.uk/site_information/allowances.cfm">explanatory notes</a>.</p>';
	$out .= '<table class="people"><tr><th>Type';
	# TODO: Needs to be more complicated at 2005/06, because of General Election
	for ($y=7; $y>=2; $y--) {
		$out .= '</th><th>200' . ($y-1) . '/0' . $y;
		if (isset($extra_info["expenses200{$y}_col1_rank_outof"])) {
			$out .= ' (ranking out of&nbsp;' . $extra_info["expenses200{$y}_col1_rank_outof"] . ')';
		}
	}
	$out .= '</th></tr>';
	$out .= '<tr><td class="row-1">Additional Costs Allowance</td>';
	$out .= expenses_row('col1', $extra_info,1);
	$out .= '</tr><tr><td class="row-2">London Supplement</td>';
	$out .= expenses_row('col2', $extra_info,2);
	$out .= '</tr><tr><td class="row-1">Incidental Expenses Provision</td>';
	$out .= expenses_row('col3', $extra_info,1);
	$out .= '</tr><tr><td class="row-2">Staffing Allowance</td>';
	$out .= expenses_row('col4', $extra_info,2);
	$out .= '</tr><tr><td class="row-1">Members\' Travel</td>';
	$out .= expenses_row('col5', $extra_info,1);
	$out .= '</tr><tr><td class="row-2">Members\' Staff Travel</td>';
	$out .= expenses_row('col6', $extra_info,2);
	$out .= '</tr><tr><td class="row-1">Centrally Purchased Stationery</td>';
	$out .= expenses_row('col7', $extra_info,1);
	$out .= '</tr><tr><td class="row-2">Stationery: Associated Postage Costs</td>';
	$out .= expenses_row('col7a', $extra_info,2);
	$out .= '</tr><tr><td class="row-1">Centrally Provided Computer Equipment</td>';
	$out .= expenses_row('col8', $extra_info,1);
	$out .= '</tr><tr><td class="row-2">Other Costs</td>';
	$out .= expenses_row('col9', $extra_info,2);
	$out .= '</tr><tr><th style="text-align: right">Total</th>';
	$out .= expenses_row('total', $extra_info,1);
	$out .= '</tr></table>';
	if (isset($extra_info['expenses2007_col5a'])) {
		$out .= '<p><a name="travel2007"></a><sup>*</sup> <small>';
		foreach(array('a'=>'Car','b'=>'3rd party','c'=>'Rail','d'=>'Air','e'=>'Other','f'=>'European') as $let => $desc) {
			if ($extra_info['expenses2007_col5'.$let] > 0) {
				$out .= $desc . ' &pound;'.number_format(str_replace(',','',$extra_info['expenses2007_col5'.$let]));
				if (isset($extra_info['expenses2007_col5'.$let.'_rank']))
					$out .= ' (' . make_ranking($extra_info['expenses2007_col5'.$let.'_rank']) . ')';
				$out .= '. ';
			}
		}
		$out .= '</small></p>';
	}
	return $out;
}

function expenses_row($col, $extra_info, $style) {
	$out = '';
	for ($ey=2007; $ey>=2002; --$ey) {
		list($amount, $rank, $extra) = expenses_item($ey, $col, $extra_info);
		if (!$amount) $amount = '&nbsp;';
		$out .= "<td class='row-$style'>$amount$rank$extra</td>\n";
	}
	return $out;
}

function expenses_item($ey, $col, $extra_info) {
	$k = 'expenses' . $ey . '_' . $col;
	$kr = $k . '_rank';
	if (isset($extra_info[$k])) {
		$amount = '&pound;'.number_format(str_replace(',','',$extra_info[$k]));
	} elseif ($col=='col7a') {
		$amount = 'N/A';
	} else {
		$amount = '';
	}
	$rank = '';
	if (isset($extra_info[$kr]) && isset($extra_info[$k]) && $extra_info[$k]>0) {
		$rank = ' (';
		if (isset($extra_info[$kr . '_joint']))
			$rank .= 'joint&nbsp;';
		$rank .= make_ranking($extra_info[$kr]) . ")";
	}
	$extra = '';
	if ($col=='col5' && $ey==2007 && isset($extra_info['expenses2007_col5a']))
		$extra = '<sup><a href="#travel2007">*</a></sup>';
	return array($amount, $rank, $extra);
}

function expenses_mostrecent($extra_info) {
	$out = '<h2>2006/07';
	if (isset($extra_info['expenses2007_col1_rank_outof'])) {
		$out .= ' (ranking out of ' . $extra_info['expenses2007_col1_rank_outof'] . ')';
	}
	$out .= '</h2>';
	$cols = array();
	for ($i=1; $i<=11; $i++) {
		if ($i==11) $r = 'total';
		elseif ($i==8) $r = 'col7a';
		elseif ($i==9 || $i==10) $r = 'col' . ($i - 1);
		else $r = "col$i";
		$row = expenses_item(2007, $r, $extra_info);
		$cols[$r] = "$row[0]$row[1]";
	}
	$out .= '<ul>';
	$out .= '<li>Additional Costs Allowance ' . $cols['col1'];
	$out .= '<li>London Supplement ' . $cols['col2'];
	$out .= '<li>Incidental Expenses Provision ' . $cols['col3'];
	$out .= '<li>Staffing Allowance ' . $cols['col4'];
	$out .= '<li>Members\' Travel ' . $cols['col5'];
	$out .= '<li>Members\' Staff Travel ' . $cols['col6'];
	$out .= '<li>Centrally Purchased Stationery ' . $cols['col7'];
	$out .= '<li>Stationery: Associated Postage Costs ' . $cols['col7a'];
	$out .= '<li>Centrally Provided Computer Equipment ' . $cols['col8'];
	$out .= '<li>Other Costs ' . $cols['col9'];
	$out .= '<li>Total ' . $cols['total'];
	$out .= '</ul>';
	return $out;
}

