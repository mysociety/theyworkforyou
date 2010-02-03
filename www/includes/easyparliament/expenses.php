<?
# Expenses related functions

function expenses_display_table($extra_info, $gadget=false) {
	$out = '<p class="italic">Figures in brackets are ranks. Data from parliament.uk (<a href="http://www.parliament.uk/mpslordsandoffices/finances.cfm">source</a>).';
	if (isset($extra_info['expenses_url']))
		$out .= ' Read <a href="' . $extra_info['expenses_url'] . '">2004/05 &ndash; 2008/09 and 1st quarter 2009/10 receipts</a>.';
	$out .= "</p>\n";
	$out .= '<table class="people"><tr><th>Type';
	# TODO: Needs to be more complicated at 2005/06, because of General Election
	for ($y=8; $y>=2; $y--) {
		$out .= '</th><th>';
		$out .= year_string($y);
		if (isset($extra_info["expenses200{$y}_col1_rank_outof"])) {
			$out .= ' (ranking out of&nbsp;' . $extra_info["expenses200{$y}_col1_rank_outof"] . ')';
		}
	}
	$out .= '</th></tr>';
	$out .= '<tr><td class="row-1">Additional Costs Allowance</td>';
	$out .= expenses_row('col1', $extra_info,1, $gadget);
	$out .= '</tr><tr><td class="row-2">London Supplement</td>';
	$out .= expenses_row('col2', $extra_info,2, $gadget);
	$out .= '</tr><tr><td class="row-1">Incidental Expenses Provision</td>';
	$out .= expenses_row('col3', $extra_info,1, $gadget);
	$out .= '</tr><tr><td class="row-2">Staffing Allowance</td>';
	$out .= expenses_row('col4', $extra_info,2, $gadget);
	$out .= '</tr><tr><td class="row-1">Communications Allowance</td>';
	$out .= expenses_row('colcomms_allowance', $extra_info,1, $gadget);
	$out .= '</tr><tr><td class="row-2">Members\' Travel</td>';
	$out .= expenses_row('col5', $extra_info,2, $gadget);
	$out .= '</tr><tr><td class="row-1">Members\' Staff Travel</td>';
	$out .= expenses_row('col6', $extra_info,1, $gadget);
	$out .= '</tr><tr><td class="row-2">Members\' Spouse Travel</td>';
	$out .= expenses_row('colspouse_travel_a', $extra_info,2, $gadget);
	$out .= '</tr><tr><td class="row-1">Members\' Family Travel</td>';
	$out .= expenses_row('colfamily_travel_a', $extra_info,1, $gadget);
	$out .= '</tr><tr><td class="row-2">Centrally Purchased Stationery</td>';
	$out .= expenses_row('col7', $extra_info,2, $gadget);
	$out .= '</tr><tr><td class="row-1">Stationery: Associated Postage Costs</td>';
	$out .= expenses_row('col7a', $extra_info,1, $gadget);
	$out .= '</tr><tr><td class="row-2">Centrally Provided Computer Equipment</td>';
	$out .= expenses_row('col8', $extra_info,2, $gadget);
	$out .= '</tr><tr><td class="row-1">Other Costs</td>';
	$out .= expenses_row('col9', $extra_info,1, $gadget);
	$out .= '</tr><tr><th style="text-align: right">Total</th>';
	$out .= expenses_row('total', $extra_info,2, $gadget);
	$out .= '</tr></table>';
	
	if (isset($extra_info['expenses2008_colmp_reg_travel_a']) and $extra_info['expenses2008_col5'] > 0){
		$out .= '<p><a name="travel2008"></a><sup>*</sup> <small>';
		$regular_travel_header = FALSE;
		foreach(array('a'=>'Mileage', 'b' => 'Rail', 'c' => 'Air', 'd' => 'Misc') as $let => $desc){
			$travel_field = $extra_info['expenses2008_colmp_reg_travel_'.$let];
			if ($travel_field > 0){
        if ($regular_travel_header == FALSE)
			    $out .= 'Regular journeys between home/constituency/Westminster: ';
				$regular_travel_header = TRUE;
				$out .= $desc . ' &pound;'.number_format(str_replace(',','',$travel_field));
				if (isset($extra_info['expenses2008_colmp_reg_travel_'.$let.'_rank']))
					$out .= ' (' . make_ranking($extra_info['expenses2008_colmp_reg_travel_'.$let.'_rank']) . ')';
				$out .= '. ';
			}
		}
               
		$other_travel_header = FALSE;
    foreach(array('a'=>'Mileage', 'b' => 'Rail', 'c' => 'Air', 'd' => 'European') as $let => $desc){
      $travel_field = $extra_info['expenses2008_colmp_other_travel_'.$let];
      if ($travel_field > 0){
        if ($other_travel_header == FALSE)
					$out .= 'Other: ';
				$other_travel_header = TRUE;
        $out .= $desc . ' &pound;'.number_format(str_replace(',','',$travel_field));
        if (isset($extra_info['expenses2008_colmp_other_travel_'.$let.'_rank']))
          $out .= ' (' . make_ranking($extra_info['expenses2008_colmp_other_travel_'.$let.'_rank']) . ')';
        $out .= '. ';
      }
    }
		$out .= '</small></p>';
	}
	
	if (isset($extra_info['expenses2007_col5a']) and $extra_info['expenses2007_col5'] > 0) {
		$out .= '<p><a name="travel2007"></a><sup>**</sup> <small>';
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

function expenses_row($col, $extra_info, $style, $gadget) {
	$out = '';
	for ($ey=2008; $ey>=2002; --$ey) {
		list($amount, $rank, $extra) = expenses_item($ey, $col, $extra_info, $gadget);
		if (!$amount) $amount = '&nbsp;';
		$out .= "<td class='row-$style'>$amount$rank$extra</td>\n";
	}
	return $out;
}

function expenses_item($ey, $col, $extra_info, $gadget) {
	$k = 'expenses' . $ey . '_' . $col;
	$kr = $k . '_rank';
	if (isset($extra_info[$k])) {
		$amount = '&pound;'.number_format(str_replace(',','',$extra_info[$k]));
	} elseif ($col=='col7a' or $col=='colfamily_travel_a' or $col == 'colspouse_travel_a' or $col == 'colcomms_allowance') {
		$amount = 'N/A';
	} else {
		$amount = '';
	}
	$rank = '';
	if (isset($extra_info[$kr]) && isset($extra_info[$k]) && $extra_info[$k]>0) {
		$rank = ' (';
		if (isset($extra_info[$kr . '_joint'])) {
		    if ($gadget) {
                        $rank .= 'Joint&nbsp;';
                    } else {
                        $rank .= 'joint&nbsp;'; 
                    }
                }
		$rank .= make_ranking($extra_info[$kr]);
		if (isset($extra_info[$kr . '_joint']) && ! $gadget) {
			$others = $extra_info[$kr . '_joint'] - 1;
			$rank .= ' with ' . $others . ' other' . ($others==1 ? '' : 's');
		}
		$rank .= ')';
	}
	$extra = '';
	if ($col=='col5' && $ey==2007 && isset($extra_info['expenses2007_col5a']) && $extra_info['expenses2007_col5'] > 0)
		$extra = '<sup><a href="#travel2007">**</a></sup>';
	if ($col=='col5' && $ey==2008 && isset($extra_info['expenses2008_colmp_reg_travel_a']) && $extra_info['expenses2008_col5'] > 0)
		$extra = '<sup><a href="#travel2008">*</a></sup>';
	return array($amount, $rank, $extra);
}

function year_string($year){
  return '200' . ($year-1) . '/0' . $year;
}

function expenses_mostrecent($extra_info, $gadget=false) {
  $out = '<div id="expenses-header"> Expenses ';
  $year = '';
  for ($ey=2008; $ey>=2002; --$ey) {
    if (isset($extra_info['expenses'.$ey.'_col1'])){
      $out .= year_string($ey-2000);
      $out .= '</div>';
      $out .= '<div id="rank-header"><h2 id="expenses-years">';
      $out .= year_string($ey-2000);
      $out .= '</h2>';
      $year = $ey;
      if (isset($extra_info['expenses'.$ey.'_col1_rank_outof'])) {
    		$out .= '<span class="overall-ranking"> (Ranking out of ' . $extra_info['expenses'.$ey.'_col1_rank_outof'] . ')</span></div>';
    	}
    	break;
    }
  }
  if ($year == '')
    return 'No expense information.';
	$cols = array();
	for ($i=1; $i<=11; $i++) {
		if ($i==11) $r = 'total';
		elseif ($i==8) $r = 'col7a';
		elseif ($i==9 || $i==10) $r = 'col' . ($i - 1);
		else $r = "col$i";
		$row = expenses_item($year, $r, $extra_info, $gadget);
		$cols[$r] = "<span class=\"expenses-raw\">$row[0]</span><span class=\"expenses-rank\">$row[1]</span>";
	}
	$other_cols = array('spouse_travel_a', 'family_travel_a', 'comms_allowance');
	foreach($other_cols as $col){
		$r = 'col' . $col;
		$row = expenses_item($year, $r, $extra_info, $gadget);
		$cols[$r] = "<span class=\"expenses-raw\">$row[0]</span><span class=\"expenses-rank\">$row[1]</span>";
	}
	$out .= '<ul id="expenses-list">';
	$out .= '<li class="odd">Additional Costs Allowance <div class="expense-value">' . $cols['col1'] . '</div>';
	$out .= '<li class="even">London Supplement <div class="expense-value">' . $cols['col2'] . '</div>';
	$out .= '<li class="odd">Incidental Expenses Provision <div class="expense-value">' . $cols['col3'] . '</div>';
	$out .= '<li class="even">Staffing Allowance <div class="expense-value">' . $cols['col4'] . '</div>';
	$out .= '<li class="odd">Communications Allowance <div class="expense-value">' . $cols['colcomms_allowance'] . '</div>';
	$out .= '<li class="even">Members\' Travel <div class="expense-value">' . $cols['col5'] . '</div>';
	$out .= '<li class="odd">Members\' Staff Travel <div class="expense-value">' . $cols['col6'] . '</div>';
	$out .= '<li class="even">Members\' Spouse Travel <div class="expense-value">' . $cols['colspouse_travel_a'] . '</div>';
        $out .= '<li class="odd">Members\' Family Travel <div class="expense-value">' . $cols['colfamily_travel_a'] . '</div>';
	$out .= '<li class="even">Centrally Purchased Stationery <div class="expense-value">' . $cols['col7'] . '</div>';
	$out .= '<li class="odd">Stationery: Associated Postage Costs <div class="expense-value">' . $cols['col7a'] . '</div>';
	$out .= '<li class="even">Centrally Provided Computer Equipment <div class="expense-value">' . $cols['col8'] . '</div>'; 
	$out .= '<li class="odd">Other Costs <div class="expense-value">' . $cols['col9'] . '</div>';
	$out .= '<li class="even" id="total">Total <div class="expense-value">' . $cols['total'] . '</div>';
	$out .= '</ul>';
	return $out;
}

