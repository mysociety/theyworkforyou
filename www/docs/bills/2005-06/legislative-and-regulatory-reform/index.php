<?php

$this_page = 'bill_index';
include_once "../../../../includes/easyparliament/init.php";
$DATA->set_page_metadata($this_page, 'heading','Legislative and Regulatory Reform Bill');
$PAGE->page_start();
$PAGE->stripe_start();
$PAGE->block_start(array ('title'=>'House of Commons - Normal Run'));
?>
<ul>

<li><a href="http://www.theyworkforyou.com/debates/?id=2006-01-11a.305.4">First reading</a> was on 11th January 2006. <a
href="http://www.publications.parliament.uk/pa/cm200506/cmbills/111/2006111.htm">Legislative and Regulatory Reform Bill (111)</a>.</li>

<li>The Regulatory Reform Committee published a <a href="http://www.publications.parliament.uk/pa/cm200506/cmselect/cmdereg/878/87802.htm">Special Report</a> on this Bill, on 31st January.</li>

<li>The Bill had its <a
href="http://www.theyworkforyou.com/debates/?id=2006-02-09a.1048.0">second
reading</a> on 9th February.</li>

<li>It then went to <a
href="http://www.publications.parliament.uk/pa/cm/cmscleg.htm">Standing
Committee A</a>, which debated it on 28th February, and 2nd, 7th, and 9th March.</li>

<li>After not being amended by Standing Committee A, it was published again: <a
href="http://www.publications.parliament.uk/pa/cm200506/cmbills/141/2006141.htm">Legislative and
Regulatory Reform Bill (141)</a>.</li>

<li><a href="http://www.publications.parliament.uk/pa/cm200506/cmselect/cmdereg/1004/100402.htm">The Government's response to the Regulatory Reform Committee's Special Report</a>, 21st March.</li>

<li>The government proposed some <a href="http://www.cabinetoffice.gov.uk/regulation/documents/pdf/amendments.pdf">amendments to the Bill</a> on 4th May.
Unfortunately, this is only in the form of one big PDF with lots of cross-referencing needed to the original Bill 141. So here's TheyWorkForYou's helpful version, showing the differences from the current version to one with all the amendments applied. <del>This means something removed from the Bill</del>, <ins>this means something new added</ins>.

<style type="text/css">
ins { color: #009900; }
del { color: #990000; }
</style>
<?

if (file_exists('diff.html')) {
	$out = file_get_contents('diff.html');
} else {
	$bill = array(); # The bill, page by page, line by line
	$clauses = array(); # The bill, clause by clause, sub-clause by sub-clause, paragraph by paragraph. Yuk.
	parse_bill('2006141.txt');
	$amendments = read_amendments('amendments.txt'); # The amendments, by number
	parse_amendments();
	$out = $title."\n\n";
	$out .= "Page,Line\n";
	foreach ($bill as $page_num => $page) {
		foreach ($page as $line_num => $line) {
			$page_num = substr(" $page_num", -2);
			$line_num = substr(" $line_num", -2);
			$out .= "$page_num,$line_num : $line<br>";
		}
	}
}
print "<pre>$out</pre>";
print '</ul>';
$PAGE->block_end();
$includes = array(
	array (
		'type' => 'include',
		'content' => 'bills_intro'
	),
);
$PAGE->stripe_end($includes);
$PAGE->page_end();

# ---

function parse_bill($f) {
	global $bill, $clauses, $title;
	$f = file($f);
	$page = 1; $line = -1;
	$clause = 0; $subclause = 0; $subsubclause = 0;
	$intitle = true;
	$title = '';
	foreach ($f as $r) {
		if ($line<1) {
			$line++;
			continue;
		}
		if ($r == "\x0c\n") {
			$page++;
			$line = -1;
			continue;
		}
		if ($r == "\n") {
			continue;
		}
		if ($intitle) {
			$title .= $r;
			if (preg_match('#as follows:--#', $r)) {
				$intitle = false;
			}
			continue;
		}
		if (substr($r, 0, 8)=='Bill 141') continue;
		if (preg_match('#\s+([1-4]?[05])$#', $r, $m)) {
			if ($line != $m[1]) {
				print "ERROR! $line $m[1] $r";
				exit;
			}
			$r = preg_replace('#\s+[1-4]?[05]$#', '', $r);
		}
		if (preg_match('#^(\d+)\s+#', $r, $m)) {
			$clause = $m[1];
			$subclause = 0;
			$subsubclause = 0;
			$clauses[$clause][$subclause][$subsubclause]['startL'] = $line;
			$clauses[$clause][$subclause][$subsubclause]['startP'] = $page;
			#$r = preg_replace('#^\d+\s+#', '', $r);
		}
		if (preg_match('#^\s+\((\d+)\)\s+#', $r, $m)) {
			$subclause = $m[1];
			$subsubclause = 0;
			$clauses[$clause][$subclause][$subsubclause]['startL'] = $line;
			$clauses[$clause][$subclause][$subsubclause]['startP'] = $page;
			#$r = preg_replace('#^  \(\d+\)\s+#', '', $r);
		}
		if (preg_match('#^\s+\(([a-h])\)\s+#', $r, $m)) {
			$subsubclause = $m[1];
			$clauses[$clause][$subclause][$subsubclause]['startL'] = $line;
			$clauses[$clause][$subclause][$subsubclause]['startP'] = $page;
			#$r = preg_replace('#^  \(\d+\)\s+#', '', $r);
		}
		$bill[$page][$line] = $r;
		$clauses[$clause][$subclause][$subsubclause]['endL'] = $line;
		$clauses[$clause][$subclause][$subsubclause]['endP'] = $page;
		$line++;
	}
}

function read_amendments($f) {
	$amendments = array();
	$f = file($f);
	$line = 1;
	$proposer = null;
	foreach ($f as $r) {
		if ($r == "\n") {
			continue;
		}
		if ($line<1) {
			$line++;
			continue;
		}
		if ($r == "\x0c\n") {
			$line = -1;
			continue;
		}
		if (preg_match('#^\S#', $r)) {
			$proposer = $r;
		} elseif (preg_match('#^\s+(\d+)$#', $r, $m)) {
			$number = $m[1];
			$amendments[$number] = '';
		} elseif (preg_match('#To move the following Clause#', $r)) {
			preg_match('#\n(.*?)$#', $amendments[$number-1], $m);
			$amendments[$number-1] = preg_replace('#\n(.*?)$#', '', $amendments[$number-1]);
			$amendments[$number] .= '*' . trim($m[1]) . "*\n$r";
		} else {
			$amendments[$number] .= $r;
		}
	}
	return $amendments;
}

function parse_amendments() {
	global $amendments, $bill, $clauses, $title;
	foreach ($amendments as $num => $amendment) {
		# Page 8, line 4 [Clause 13], leave out `21-day' and insert `30-day'
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], leave out `(.*?)\' and insert `(.*?)\'#s', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$delete = $m[4]; $insert = $m[5];
			unset($amendments[$num]);
			$bill[$page][$line] = preg_replace("#(.*)$delete#", "$1<del title='$num'>$delete</del><ins title='$num'>$insert</ins>", $bill[$page][$line]);
		}
		# Page   2, line 32 [Clause 3], leave out from `make' to end of line 35 and insert `...'
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], leave out from `(.*?)\' to end of line (\d+) and insert(?:--)?\s+`(.*?)\'#s', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$from_text = $m[4]; $end_line = $m[5]; $insert = $m[6];
			unset($amendments[$num]);
			$bill[$page][$line] = str_replace($from_text, "$from_text <del title='$num'>", $bill[$page][$line]) . '</del><ins title="'.$num.'">' . $insert . '</ins>';
			for ($i=$line+1; $i<=$end_line; $i++) {
				$bill[$page][$i] = '<del title="'.$num.'">' . $bill[$page][$i] . '</del>';
			}
		}
		# Page  4, line 9 [Clause 6], leave out from `under' to `creating' and insert `this Part making provision' 
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], leave out from `(.*?)\' to `(.*?)\' and insert `(.*?)\'#s', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$from_text = $m[4]; $to_text = $m[5]; $insert = $m[6];
			unset($amendments[$num]);
			$bill[$page][$line] = preg_replace("#$from_text(.*?)$to_text#", "$from_text <del title='$num'>$1</del><ins title='$num'>$insert</ins> $to_text", $bill[$page][$line]);
		}
		# Page  7, line 1 [Clause 12], leave out from `of' to `the' in line 2 and insert `...' 
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], leave out from `(.*?)\' to `(.*?)\' in line (\d+) and insert `(.*?)\'#s', $amendment, $m)) {
			$page = $m[1]; $from_line = $m[2]; $clause = $m[3];
			$from_text = $m[4]; $to_text = $m[5]; $to_line = $m[6];
			$insert = $m[7];
			unset($amendments[$num]);
			$bill[$page][$from_line] = str_replace($from_text, "$from_text <del title='$num'>", $bill[$page][$from_line]) . '</del>';
			for ($i=$from_line+1; $i<$to_line; $i++) {
				$bill[$page][$i] = '<del title="'.$num.'">' . $bill[$page][$i] . '</del>';
			}
			$bill[$page][$to_line] = '<del title="'.$num.'">' . str_replace($to_text, "</del><ins title='$num'>$insert</ins> $to_text", $bill[$page][$to_line]);
		}
		# Page  3, line 13 [Clause 4], leave out from beginning to `confer' and insert `An order under this Part may not make provision to'
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], leave out from beginning to `(.*?)\' and insert `(.*?)\'#s', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$to_text = $m[4]; $insert = $m[5];
			unset($amendments[$num]);
			$bill[$page][$line] = "<ins title='$num'>$insert</ins><del title='$num'>" . str_replace($to_text, "</del> $to_text", $bill[$page][$line]);
		}
		# Page   19, line 2 [Clause 34], leave out from `under' to the end of the line and insert `...'
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], leave out from `(.*?)\' to the end of the line and insert\s+`(.*?)\'#s', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$from_text = $m[4]; $insert = $m[5];
			unset($amendments[$num]);
			$bill[$page][$line] = str_replace($from_text, "$from_text <del title='$num'>", $bill[$page][$line]) . "</del><ins title='$num'>$insert</ins>";
		}
		# Page   4 [Clause 7], leave out line 26 and insert `An order under this Part may not make provision to--' 
		if (preg_match('#Page\s+(\d+) \[Clause (\d+)\], leave out line (\d+)(?: and insert `(.*?)\')?#', $amendment, $m)) {
			$page = $m[1]; $line = $m[3]; $clause = $m[2];
			$insert = isset($m[4]) ? $m[4] : null;
			unset($amendments[$num]);
			$bill[$page][$line] = '<del title="'.$num.'">'.$bill[$page][$line].'</del>';
			if ($insert) $bill[$page][$line] .= '<ins title="'.$num.'">'.$insert.'</ins>';
		}
		# Page 8, line 24 [Clause 14], at end insert-- `...'
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], (?:at end|after subsection \(\d+\)) insert--\s+`(.*?)\'#s', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$insert = $m[4];
			unset($amendments[$num]);
			$bill[$page][$line] .= '<ins title="'.$num.'">'.$insert.'</ins>';
		}
		# Title, line    1, leave out `reforming legislation' and insert `...'
		if (preg_match('#Title, line.*?, leave out `(.*?)\' and insert `(.*?)\'#s', $amendment, $m)) {
			$delete = $m[1]; $insert = $m[2];
			unset($amendments[$num]);
			$title = str_replace($delete, "<del title='$num'>$delete</del><ins title='$num'>$insert</ins>", $title);
		}
		# Page 4, line 23 [Clause 6], leave out paragraph (b)
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], leave out paragraph \((.*?)\)(?: and insert-- `(.*?)\')?#', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$paragraph = $m[4];
			$insert = isset($m[5]) ? $m[5] : null;
			foreach ($clauses[$clause] as $subclause_num => $subclause) {
				foreach ($subclause as $subsubclause_num => $subsubclause) {
					$startP = $subsubclause['startP'];
					if ($startP==$page && $subsubclause['startL']==$line) {
						if ($startP == $subsubclause['endP']) {
							unset($amendments[$num]);
							for ($i = $subsubclause['startL']; $i<=$subsubclause['endL']; $i++) {
								$bill[$page][$i] = '<del title="'.$num.'">' . $bill[$page][$i] . '</del>';
							}
							if ($insert) {
								$bill[$page][$i-1] .= "<ins title='$num'>$insert</ins>";
							}
						}
					}
				}
			}
		}
		# Page 6, line 40 [Clause 12], leave out subsection (3)
		if (preg_match('#Page\s+(\d+), line (\d+) \[Clause (\d+)\], leave out subsection \((.*?)\)(?: and insert-- `(.*?)\')?#', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$subsection = $m[4];
			$insert = isset($m[5]) ? $m[5] : null;
			$finished = false;
			foreach ($clauses[$clause] as $subclause_num => $subclause) {
				foreach ($subclause as $subsubclause_num => $subsubclause) {
					$startP = $subsubclause['startP'];
					if ($startP==$page && $subsubclause['startL']==$line) {
						if ($startP == $subsubclause['endP']) {
							unset($amendments[$num]);
							$finished = true;
						}
					}
					if ($finished) {
						for ($i = $subsubclause['startL']; $i<=$subsubclause['endL']; $i++) {
							$bill[$page][$i] = '<del title="'.$num.'">' . $bill[$page][$i] . '</del>';
						}
					}
				}
				if ($finished) {
					if ($insert) {
						$bill[$page][$i-1] .= "<ins title='$num'>$insert</ins>";
					}
					break;
				}
			}
		}
		# Page 12, line 17, leave out clause 24
		if (preg_match('#Page\s+(\d+), line (\d+), leave out clause (\d+)#', $amendment, $m)) {
			$page = $m[1]; $line = $m[2]; $clause = $m[3];
			$finished = false;
			foreach ($clauses[$clause] as $subclause_num => $subclause) {
				foreach ($subclause as $subsubclause_num => $subsubclause) {
					if ($subsubclause['startP']==$page && $subsubclause['startL']==$line) {
						unset($amendments[$num]);
						$finished = true;
					}
					if ($finished) {
						for ($p = $subsubclause['startP']; $p<=$subsubclause['endP']; $p++) {
							if ($p>$subsubclause['startP']) $starti = 1;
							else $starti = $subsubclause['startL'];
							for ($i = $starti; $i<=$subsubclause['endL']; $i++) { # XXX Doesn't really work spanning pages
								$bill[$p][$i] = '<del title="'.$num.'">' . $bill[$p][$i] . '</del>';
							}
						}
					}
				}
			}
		}
	
		# New clause
		if (preg_match('#^\*(.*?)\*\s+(.*?)$#s', $amendment, $m)) {
			unset($amendments[$num]);
			$page = 0;
			$line = 0;
			foreach ($clauses[$clause] as $subclause_num => $subclause) {
				foreach ($subclause as $subsubclause_num => $subsubclause) {
					if ($subsubclause['endP'] > $page) { $page = $subsubclause['endP']; $line = 0; }
					if ($subsubclause['endL'] > $line) $line = $subsubclause['endL'];
				}
			}
			$bill[$page][$line] .= "<ins title='$num'>$amendment</ins>\n\n";
		}
	}
}

?>
