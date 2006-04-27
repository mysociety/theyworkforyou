<?php

$dir = '/home/fawkes/parldata/scrapedxml/regmem';
$dh = opendir($dir);
$files = array();
while ($file = readdir($dh)) {
	if (preg_match('#^regmem#', $file))
		$files[] = "$dir/$file";
}
rsort($files);

include_once "../../includes/easyparliament/init.php";
$PAGE->page_start();
?>
<style type="text/css">
blockquote { background-color: #f5fdea; border: solid 1px #4d6c25; padding: 3px; }
td { vertical-align: top; }
.a { background-color: #ccffcc; margin-bottom: 0.5em; }
.r { background-color: #ffcccc; margin-bottom: 0.5em; }
th { text-align: left; }
table#regmem h4 { margin: 0; margin-top: 0.5em; padding-top: 0.5em; border-top: dotted 1px #333333; }
#regmem h5 { margin: 0; border-bottom: dotted 1px #cccccc; }
#mps li {
	float: left;
	width: 23%;
}
</style>
<?
$f = get_http_var('f'); if (!preg_match('#^\d\d\d\d-\d\d-\d\d$#', $f)) $f='';
$p = get_http_var('p'); if (!ctype_digit($p)) $p='';
$d = get_http_var('d'); if (!preg_match('#^\d\d\d\d-\d\d-\d\d$#', $d)) $d='';

$link = '<p align="center"><a href="./"><strong>List all MPs and Register editions</strong></a></p>';
if ($f)
	register_history($f);
elseif ($p)
	person_history($p);
elseif ($d)
	show_register($d);
else {
	$this_page = 'regmem';
	$PAGE->stripe_start();
	front_page();
}
$PAGE->stripe_end();
$PAGE->page_end();

function person_history($p) {
	global $files, $dir, $DATA, $PAGE, $this_page, $link, $cats;
	$this_page = 'regmem_mp';
	$name = '';
	$nil = array();
	$earliest = $files[0];
	foreach ($files as $_) {
		$file = file_get_contents($_);
		$data[$_] = array();
		if (preg_match('#<regmem personid="uk.org.publicwhip/person/'.$p.'" memberid="(.*?)" membername="(.*?)" date="(.*?)">(.*?)</regmem>#s', $file, $m)) {
			$earliest = $_;
			if (!$name) {
				$name = $m[2];
				$DATA->set_page_metadata($this_page, 'heading', $name);
				$PAGE->stripe_start();
				print $link;
				?>
<p>This page shows how <a href="/mp/?p=<?=$p ?>"><?=$name ?></a>'s entry in the Register of Members' Interests has changed over time, starting at the most recent and working back to the earliest we have managed to parse.</p>
<table id="regmem">
<tr><th width="50%">Removed</th><th width="50%">Added</th></tr>
<?
			}
			$name = $m[2]; $ddata = $m[4];
			if (preg_match('/Nil\./', $ddata)) $nil[$_] = true;
			preg_match_all('#<category type="(.*?)" name="(.*?)">(.*?)</category>#s', $ddata, $mm, PREG_SET_ORDER);
			foreach ($mm as $k => $m) {
				$cat_type = $m[1];
				$cat_name = $m[2];
				$cats[$cat_type] = $cat_name;
				$cat_data = canonicalise_data($m[3]);
				$data[$_][$cat_type] = $cat_data;
			}
		}
	}

	$out = '';
	foreach ($files as $i => $_) {
		if ($_ <= $earliest) break;
		$iso = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $_);
		$pretty = format_date($iso, LONGDATEFORMAT);
		$oout = '';
		foreach ($data[$_] as $cat_type => $cat_data) {
			$old = array_key_exists($cat_type, $data[$files[$i+1]]) ? $data[$files[$i+1]][$cat_type] : '';
			$new = $data[$_][$cat_type];
			if ($diff = clean_diff($old, $new))
				$oout .= cat_heading($cat_type) . $diff;
		}
		foreach ($data[$files[$i+1]] as $cat_type => $cat_data) {
			if (array_key_exists($cat_type, $data[$_])) continue;
			if ($diff = clean_diff($data[$files[$i+1]][$cat_type], ''))
				$oout .= cat_heading($cat_type) . $diff;
		}
		if ($oout)
			$out .= span_row("<h4>$pretty - <a href=\"./?d=$iso#$p\">View full entry</a></h4>", true) . $oout;
	}
	$_ = $earliest;
	$pretty = format_date(preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $_), LONGDATEFORMAT);
	$out .= span_row("<h4>$pretty (first entry we have)</h4>", true);
	if (array_key_exists($_, $nil)) {
		$out .= span_row('Nothing');
	}
	foreach ($data[$_] as $cat_type => $d) {
		$out .= cat_heading($cat_type);
		$out .= span_row(prettify($d));
	}
	print $out;
	if ($name) print '</table>';
}

function register_history($f) {
	global $dir, $files, $cats, $names, $DATA, $PAGE, $link, $this_page;
	$this_page = 'regmem_diff';
	$new = 0;
	if ($f) {
		$f = "$dir/regmem$f.xml";
		for ($i=0; $i<count($files); ++$i) {
			if ($files[$i] == $f) {
				$new = $i;
				break;
			}
		}
	}
	$old = $new+1;
	$old = $files[$old];
	$old_iso = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $old);
	$old_pretty = format_date($old_iso, LONGDATEFORMAT);
	$new = $files[$new];
	$new_iso = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $new);
	$new_pretty = format_date($new_iso, LONGDATEFORMAT);
	$old = file_get_contents($old);
	$new = file_get_contents($new);

	$DATA->set_page_metadata($this_page, 'heading', 'Changes from '.$old_pretty.' to '.$new_pretty);
	$PAGE->stripe_start();
	print $link;
	$data = array();
	parse_file($old, 'old', $data);
	parse_file($new, 'new', $data);
?>
<p>This page shows all the changes in the Register of Members' Interests between the editions of <a href="./?d=<?=$old_iso ?>"><?=$old_pretty ?></a> and <a href="./?d=<?=$new_iso ?>"><?=$new_pretty ?></a>, in alphabetical order by MP.</p>
<table cellpadding="3" cellspacing="0" border="0" id="regmem">
<tr><th width="50%">Removed</th><th width="50%">Added</th></tr>
<?

	uksort($data, 'by_name_ref');
	foreach ($data as $person_id => $v) {
		$out = '';
		foreach ($v as $cat_type => $vv) {
			$out .= cat_heading($cat_type);
			$old = (array_key_exists('old', $data[$person_id][$cat_type]) ? $data[$person_id][$cat_type]['old'] : '');
			$new = (array_key_exists('new', $data[$person_id][$cat_type]) ? $data[$person_id][$cat_type]['new'] : '');
			$out .= clean_diff($old, $new);
		}
		if ($out) {
			print span_row('<h4>'.$names[$person_id].' - <a href="?p='.$person_id.'">Register history</a> | <a href="http://www.theyworkforyou.com/mp/?pid='.$person_id.'">MP\'s page</a></h4>', true) . $out;
		}
	}
	print '</table>';
}

function by_name_ref($a, $b) {
	global $names;
	$a = preg_replace('/^.* /', '', $names[$a]);
	$b = preg_replace('/^.* /', '', $names[$b]);
	if ($a > $b) return 1;
	elseif ($a < $b) return -1;
	return 0;
}

function parse_file($file, $type, &$out) {
	global $cats, $names;
	preg_match_all('#<regmem personid="uk.org.publicwhip/person/(.*?)" memberid="(.*?)" membername="(.*?)" date="(.*?)">(.*?)</regmem>#s', $file, $mm, PREG_SET_ORDER);
	foreach ($mm as $k => $m) {
		$person_id = $m[1]; $name = $m[3]; $data = $m[5];
		$names[$person_id] = $name;
		preg_match_all('#<category type="(.*?)" name="(.*?)">(.*?)</category>#s', $data, $mmm, PREG_SET_ORDER);
		foreach ($mmm as $k => $m) {
			$cat_type = $m[1];
			$cat_name = $m[2];
			$cats[$cat_type] = $cat_name;
			$cat_data = canonicalise_data($m[3]);
			$out[$person_id][$cat_type][$type] = $cat_data;
			if ($type == 'new' && array_key_exists('old', $out[$person_id][$cat_type]) && $cat_data == $out[$person_id][$cat_type]['old']) {
					unset($out[$person_id][$cat_type]);
			}
		}
	}
}

function front_page() {
	global $files;
	foreach ($files as $_) {
		$file = file_get_contents($_);
		preg_match_all('#<regmem personid="uk.org.publicwhip/person/(.*?)" memberid="(.*?)" membername="(.*?)" date="(.*?)">(.*?)</regmem>#s', $file, $m, PREG_SET_ORDER);
		foreach ($m as $k => $v) {
			$person_id = $v[1]; $name = $v[3]; $data = $v[5];
			$names[$person_id] = $name;
		}
	}
	$c = 0; $year = 0;
	$view = ''; $compare = '';
	for ($i=0; $i<count($files); ++$i) {
		preg_match('/(\d\d\d\d)-(\d\d-\d\d)/', $files[$i], $m);
		$y = $m[1]; $md = $m[2];
		if ($c++) {
			$view .= ' | ';
			if ($i<count($files)-1) $compare .= ' | ';
		}
		if ($year != $y) {
			$year = $y;
			$view .= "<em>$year</em> ";
			if ($i<count($files)-1) $compare .= "<em>$year</em> ";
		}
		$months = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
		preg_match('/(\d\d)-(\d\d)/', $md, $m);
		$date = ($m[2]+0) . ' '. $months[$m[1]-1];
		$view .= '<a href="./?d='.$y.'-'.$md.'">'.$date.'</a>';
		if ($i<count($files)-1) $compare .= '<a href="?f='.$y.'-'.$md.'">'.$date.'</a>';
	}
?>
<p>This section of the site lets you see how MPs' entries in the Register of Members' Interests have changed over time, either by MP, or for a particular issue of the Register.</p>

<blockquote>
<p style="margin-top:0">The financial thresholds over which an interest must be registered are mainly based, for convenience, on percentages of an MP's salary: one per cent, or currently £590, for employment, gifts and hospitality; ten per cent, or £5,900, for rental income; and a hundred per cent, or £59,000, for property and shares. The exception is sponsorship, where the threshold has been set at £1,000 to match that set for registration with the Electoral Commission.</p>
<p>Continuing interests like employment or property remain on the Register until the Member asks for them to be removed. 'One-off' benefits like gifts, visits and donations appear with their date of registration and remain on the Register for a year from that date and until they have appeared in one printed Register.</p>
<p style="margin-bottom:0" align="right">&mdash; <a href="http://www.publications.parliament.uk/pa/cm/cmregmem/051214/memi01.htm">http://www.publications.parliament.uk/pa/cm/cmregmem/051214/memi01.htm</a> (with more information).</p>
</blockquote>
<p>So, either <strong>pick an issue to compare against the one previous:</strong></p>
<p align="center"><?=$compare ?></p>
<p><strong>View a particular edition of the Register of Members' Interests:</strong></p>
<p align="center"><?=$view ?></p>
<p>Or <strong>view the history of an MP's entry in the Register:</strong></p> <ul id="mps">
<?
	uasort($names, 'by_name');
	foreach ($names as $_ => $value) {
		print '<li><a href="?p='.$_.'">'.$value.'</a>';
	}
	print '</ul>';

}

function show_register($d) {
	global $dir, $files, $cats, $names, $PAGE, $DATA, $this_page, $link;
	$d = "$dir/regmem$d.xml";
	if (!in_array($d, $files))
		$d = $files[0];
	$d_iso = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $d);
	$d_pretty = format_date($d_iso, LONGDATEFORMAT);
	$d = file_get_contents($d);
	$data = array();
	parse_file($d, 'only', $data);
	$this_page = 'regmem_date';
	$DATA->set_page_metadata($this_page, 'heading', "The Register of Members' Interests, $d_pretty");;
	$PAGE->stripe_start();
	print $link;
?>
<p>This page shows the Register of Members' Interests as released on <?=$d_pretty ?>, in alphabetical order by MP.
<? if ($d_iso > '2002-05-14') { ?><a href="./?f=<?=$d_iso ?>">Compare this edition with the one before it</a></p><? } ?>
<div id="regmem">
<?
	uksort($data, 'by_name_ref');
	foreach ($data as $person_id => $v) {
		$out = '';
		foreach ($v as $cat_type => $vv) {
			$out .= cat_heading($cat_type, false);
			$d = (array_key_exists('only', $data[$person_id][$cat_type]) ? $data[$person_id][$cat_type]['only'] : '');
			$out .= prettify($d)."\n";
		}
		if ($out) {
			$PAGE->block_start(array('title'=>'<a name="'.$person_id.'"></a>'.$names[$person_id].' - <a href="?p='.$person_id.'">Register history</a> | <a href="http://www.theyworkforyou.com/mp/?pid='.$person_id.'">MP\'s page</a>'));
			print "\n$out";
			$PAGE->block_end();
		}
	}
	print '</div>';
}

function by_name($a, $b) {
	$a = preg_replace('/^.* /', '', $a);
	$b = preg_replace('/^.* /', '', $b);
	if ($a > $b) return 1;
	elseif ($a < $b) return -1;
	return 0;
}
function canonicalise_data($cat_data) {
	$cat_data = preg_replace('#^.*?<item#s', '<item', $cat_data);
	$cat_data = str_replace(array('<i>', '</i>'), '', $cat_data);
	$cat_data = preg_replace('/<item subcategory="(.*?)">\s*/', '<item>($1) ', $cat_data);
	$cat_data = preg_replace('/<item([^>]*?)>\s*/', '<item>', $cat_data);
	$cat_data = preg_replace('/  +/', ' ', $cat_data);
	$cat_data = preg_replace('# (\d{1,2})th #', ' $1<sup>th</sup> ', $cat_data);
	return $cat_data;
}

function clean_diff($old, $new) {
	$old = explode("\n", $old);
	$new = explode("\n", $new);
	$r = array_diff($old, $new);
	$a = array_diff($new, $old);
	if (!count($r) && !count($a)) return '';
	$r = join("\n", $r); $r = $r ? '<td class="r"><ul>'.$r.'</ul></td>' : '<td>&nbsp;</td>';
	$a = join("\n", $a); $a = $a ? '<td class="a"><ul>'.$a.'</ul></td>' : '<td>&nbsp;</td>';
	$diff = '<tr>' . $r . $a . '</tr>';
	$diff = preg_replace('#<item.*?>(.*?)</item>#', '<li>$1</li>', $diff);
	return $diff;
}

function prettify($s) {
	$s = preg_replace('#<item>(.*?)</item>#', '<li>$1</li>', $s);
	return "<ul>$s</ul>";
}

function cat_heading($cat_type, $table = true) {
	global $cats;
	$row = "<h5>$cat_type. $cats[$cat_type]</h5>";
	if ($table)
		return span_row($row, true);
	return $row;
}

function span_row($s, $heading = false) {
	if ($heading)
		return "<tr><th colspan=\"2\">$s</th></tr>\n";
	return "<tr><td colspan=\"2\">$s</td></tr>\n";
}

