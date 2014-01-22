<?php
# Expenses related functions

function expenses_display_table($extra_info, $gadget=false, $start_year=9) {
        $out = '';
        $latest_year = 9; 
        $earliest_year = 2;
        if ($start_year > $latest_year or $start_year < $earliest_year) {
             $start_year = 9;
        }
        $end_year = $earliest_year;        
        if ($gadget) {
             $first_year_with_data = '';
             for ($ey=2000+$latest_year; $ey>=2000+$earliest_year; --$ey) {
	         if (isset($extra_info['expenses'.($ey).'_col1'])){
                      $first_year_with_data = $ey;
                      break;
                 } 		
             }
             if ($first_year_with_data == ''){
                  return '';
             }
             $out .= "<h2>Expenses</h2>";
             $end_year = $start_year - 2;
             $out .= '<div class="other-expenses-links">';
             $out .= '<div class="earlier-expenses-link">';
             if ($end_year > $earliest_year) {
                  $next_year = 2000 + $end_year - 1;
                  $out .= "<p><a href=\"?start_year=$next_year\">See earlier
expenses</a></p>";
             } else {
                  $end_year = 2;
             }
             $out .= '</div>';

             $out .= '<div class="later-expenses-link">';
             if ($start_year < $latest_year) { 
                  $previous_year = 2000 + $start_year + 3;
                  $out .= "<p><a href=\"?start_year=$previous_year\">See later 
expenses</a></p>";
             }
             $out .= '</div>';
             $out .= '</div>';   
        } else {
	     $out = '<p class="italic">Figures in brackets are ranks.';
             $out .= 'Data from parliament.uk (<a href="http://www.parliament.uk/mpslordsandoffices/finances.cfm">source</a>).';
	     if (isset($extra_info['expenses_url']))
	     	$out .= ' Read <a href="' . $extra_info['expenses_url'] . '">2004/05 &ndash; 2008/09 and 1st quarter 2009/10 receipts</a>.';
	     $out .= "</p>\n";
        }         
        $out .= '<table class="people">';
        $out .= '<tr><th class="left">Type';
        $wide_year = $end_year + 3;
        $med_year = $end_year + 5;
	# TODO: Needs to be more complicated at 2005/06, because of General Election
	for ($y=$start_year; $y>=$end_year; $y--) {
                $class = '';
                $responsive_class = '';
                if ( $y <= $wide_year ) {
                    $responsive_class = 'show-for-large-up';
                } else if ( $y <= $med_year ) {
                    $responsive_class = 'show-for-medium-up';
                }
                if ($y == $end_year) {
                    $class = "class='right $responsive_class'";
                } else {
                    $class = "class='$responsive_class'";
                }
		$out .= "</th><th $class>";
		$out .= year_string($y);
		if (isset($extra_info["expenses200{$y}_col1_rank_outof"])) {
			$out .= ' <span class="overall-ranking"> (ranking out of&nbsp;' . $extra_info["expenses200{$y}_col1_rank_outof"] . ')</span>';
		}
	}
	$out .= '</th></tr>';
  $out .= '<tbody>';
	$out .= '<tr><td class="row-1 left">Staying away from main home</td>';
	$out .= expenses_row('col1', $extra_info,1, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-2 left">London costs</td>';
	$out .= expenses_row('col2', $extra_info,2, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-1 left">Office running costs</td>';
	$out .= expenses_row('col3', $extra_info,1, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-2 left" >Staffing costs</td>';
	$out .= expenses_row('col4', $extra_info,2, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-1 left">Communications Allowance</td>';
	$out .= expenses_row('colcomms_allowance', $extra_info,1, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-2 left">Members\' Travel</td>';
	$out .= expenses_row('col5', $extra_info,2, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-1 left">Members\' Staff Travel</td>';
	$out .= expenses_row('col6', $extra_info,1, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-2 left">Members\' Spouse Travel</td>';
	$out .= expenses_row('colspouse_travel_a', $extra_info,2, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-1 left">Members\' Family Travel</td>';
	$out .= expenses_row('colfamily_travel_a', $extra_info,1, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-2 left">Centrally Purchased Stationery</td>';
	$out .= expenses_row('col7', $extra_info,2, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-1 left">Stationery: Associated Postage Costs</td>';
	$out .= expenses_row('col7a', $extra_info,1, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-2 left">Centrally Provided Computer Equipment</td>';
	$out .= expenses_row('col8', $extra_info,2, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><td class="row-1 left">Other Costs</td>';
	$out .= expenses_row('col9', $extra_info,1, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr><tr><th class="left total">Total</th>';
	$out .= expenses_row('total', $extra_info,2, $gadget, $start_year, $end_year, $med_year, $wide_year);
	$out .= '</tr></tbody></table>';

        if (isset($extra_info['expenses2009_colmp_reg_travel_a']) and $extra_info['expenses2009_col5'] > 0 and $start_year 
>= 9 and $end_year <= 9){
            $out .= expenses_extra_travel($extra_info, 2009);
        }

        if (isset($extra_info['expenses2008_colmp_reg_travel_a']) and $extra_info['expenses2008_col5'] > 0 and $start_year >= 
8 and $end_year <= 8) {
            $out .= expenses_extra_travel($extra_info, 2008);
        }

	if (isset($extra_info['expenses2007_col5a']) and $extra_info['expenses2007_col5'] > 0 and $start_year >= 7 and 
$end_year <= 7) {
		$out .= '<p class="extra-travel-info"><a name="travel2007"></a><sup>3</sup> <small>';
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

        if ($gadget) {
             $out .= '<p class="extra-info">Figures in brackets are ranks.<br>'; 
             $out .= 'Source: Parliament\'s <a href="http://www.parliament.uk/mpslordsandoffices/finances.cfm">Members\' Allowances</a>';
             if (isset($extra_info['expenses_url']))
                $out .= '<br/> Read <a href="' . $extra_info['expenses_url'] . '">2004/05 &ndash; 2008/09 and 1st quarter 2009/10 receipts</a>.';
             $out .= "</p>\n";
        }
	return $out;

}

function expenses_row($col, $extra_info, $style, $gadget, $start_year, $end_year, $med_year, $wide_year) {
	$out = '';
        $start_year = 2000 + (int) $start_year;
        $end_year = 2000 + (int) $end_year; 
        $med_year = 2000 + (int) $med_year;
        $wide_year = 2000 + (int) $wide_year;
	for ($ey=$start_year; $ey>=$end_year; --$ey) {
           $extra_class = '';
	   list($amount, $rank, $extra) = expenses_item($ey, $col, $extra_info, $gadget);
	   if (!$amount) $amount = '&nbsp;';
           $rowspan = '';
           if ($col=='col7' && $ey==2009) {
               $rowspan = " rowspan='2' style='vertical-align: middle'";
               $extra_class = 'aggregate-value';
           } 
           if ($ey == $end_year) $extra_class = 'right'; 
            if ( $ey <= $wide_year ) {
                $extra_class .= ' show-for-large-up';
            } else if ( $ey <= $med_year ) {
                $extra_class .= ' show-for-medium-up';
            }
           if ($col=='col7a' && $ey==2009) continue;
           $out .= "<td class='row-$style $extra_class'$rowspan>$amount$rank$extra</td>\n";
	}
	return $out;
}

function expenses_item($ey, $col, $extra_info, $gadget) {
    if ($col=='col7' && $ey==2009) {
        $col = 'colstationery';
    }
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
		$rank = '<span class="rank"> (';
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
		$rank .= ')</span>';
	}
	$extra = '';
	if ($col=='col5' && $ey==2007 && isset($extra_info['expenses2007_col5a']) && $extra_info['expenses2007_col5'] > 0)
		$extra = '<span class="expenses-note-link"><sup><a href="#travel2007">3</a></sup></span>';
	if ($col=='col5' && $ey==2008 && isset($extra_info['expenses2008_colmp_reg_travel_a']) && $extra_info['expenses2008_col5'] > 0)
		$extra = '<sup><a href="#travel2008">2</a></sup>';
	if ($col=='col5' && $ey==2009 && isset($extra_info['expenses2009_colmp_reg_travel_a']) && $extra_info['expenses2009_col5'] > 0)
		$extra = '<sup><a href="#travel2009">1</a></sup>';
	return array($amount, $rank, $extra);
}

function expenses_extra_travel($extra_info, $year) {
    $out = '<p class="extra-travel-info"><a name="travel' . $year . '"></a><sup>' . (2010 - $year) . '</sup> <small>';
    $regular_travel_header = FALSE;
    foreach(array('a'=>'Mileage', 'b' => 'Rail', 'c' => 'Air', 'd' => 'Misc') as $let => $desc){
        $travel_field = $extra_info['expenses' . $year . '_colmp_reg_travel_'.$let];
        if ($travel_field > 0){
            if ($regular_travel_header == FALSE)
                $out .= 'Regular journeys between home/constituency/Westminster: ';
            $regular_travel_header = TRUE;
            $out .= $desc . ' &pound;'.number_format(str_replace(',','',$travel_field));
            if (isset($extra_info['expenses' . $year . '_colmp_reg_travel_'.$let.'_rank']))
                $out .= ' (' . make_ranking($extra_info['expenses' . $year . '_colmp_reg_travel_'.$let.'_rank']) . ')';
            $out .= '. ';
        }
    }
               
    $other_travel_header = FALSE;
    foreach(array('a'=>'Mileage', 'b' => 'Rail', 'c' => 'Air', 'd' => 'European') as $let => $desc){
        $travel_field = $extra_info['expenses' . $year . '_colmp_other_travel_'.$let];
        if ($travel_field > 0){
            if ($other_travel_header == FALSE)
                $out .= 'Other: ';
            $other_travel_header = TRUE;
            $out .= $desc . ' &pound;'.number_format(str_replace(',','',$travel_field));
            if (isset($extra_info['expenses' . $year . '_colmp_other_travel_'.$let.'_rank']))
                $out .= ' (' . make_ranking($extra_info['expenses' . $year . '_colmp_other_travel_'.$let.'_rank']) . ')';
            $out .= '. ';
        }
    }
    $out .= '</small></p>';
    return $out;
}

function year_string($year) {
  return '200' . ($year-1) . '/0' . $year;
}

function expenses_mostrecent($extra_info, $gadget=false) {
  $out = '<div id="expenses-header"> Expenses ';
  $year = '';
  for ($ey=2009; $ey>=2002; --$ey) {
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
    return '';
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
