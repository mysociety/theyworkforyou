<?php

include_once '../../includes/easyparliament/init.php';

$chamber = get_http_var('chamber');

$dir = RAWDATA . 'scrapedxml/regmem';

// Set the directory based on the chamber
if ($chamber == "northern-ireland-assembly") {
    $dir = RAWDATA . 'scrapedxml/regmem-ni';
} elseif ($chamber == "scottish-parliament") {
    $dir = RAWDATA . 'scrapedxml/regmem-scotparl';
} elseif ($chamber == "senedd") {
    if (LANGUAGE == "en") {
        $dir = RAWDATA . 'scrapedxml/regmem-senedd-en';
    } elseif (LANGUAGE == "cy") {
        $dir = RAWDATA . 'scrapedxml/regmem-senedd-cy';
    }
}
$dh = opendir($dir);
$files = [];
while ($file = readdir($dh)) {
    if (preg_match('#^regmem#', $file)) {
        $files[] = "$dir/$file";
    }
}
rsort($files);

if (!DEVSITE) {
    header('Cache-Control: max-age=3600');
}

$PAGE->page_start();
?>
<style type="text/css">
blockquote { background-color: #f5fdea; border: solid 1px #4d6c25; padding: 3px; }
td { vertical-align: top; }
.a { background-color: #ccffcc; margin-bottom: 0.5em; }
.r { background-color: #ffcccc; margin-bottom: 0.5em; }
th { text-align: left; }
table#regmem h2 { margin: 0; margin-top: 0.5em; padding-top: 0.5em; border-top: dotted 1px #333333; }
#regmem h3 { margin: 0; border-bottom: dotted 1px #cccccc; }
#mps li {
    float: left;
    width: 23%;
}
</style>
<?php
$f = get_http_var('f');
if (!preg_match('#^\d\d\d\d-\d\d-\d\d$#', $f)) {
    $f = '';
}
$p = (int) get_http_var('p');
$d = get_http_var('d');
if (!preg_match('#^\d\d\d\d-\d\d-\d\d$#', $d)) {
    $d = '';
}

$link = '<p align="center"><a href="./"><strong>List all MPs and Register editions</strong></a></p>';
if ($f) {
    register_history($f);
} elseif ($p) {
    person_history($p);
} elseif ($d) {
    show_register($d);
} else {
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
    $nil = [];
    $earliest = $files[0];
    foreach ($files as $_) {
        $file = _load_file($_);
        $date = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $_);
        $data[$_] = [];
        if (preg_match('#<regmem personid="uk.org.publicwhip/person/' . $p . '" (?:memberid="(.*?)" )?membername="(.*?)" date="(.*?)">(.*?)</regmem>#s', $file, $m)) {
            $earliest = $_;
            if (!$name) {
                $name = $m[2];
                $DATA->set_page_metadata($this_page, 'heading', $name);
                $PAGE->stripe_start();
                print $link;
                ?>
<p>
<?= sprintf(gettext("This page shows how %s's entry in the Register of Members' Interests has changed over time, starting at the most recent and working back to the earliest we have managed to parse."), "<a href=\"/mp/?p=$p\">$name</a>") ?></p>
<p>
<?= gettext("Please be aware that changes in typography/styling at the source might mean something is marked as changed (ie. removed and added) when it hasn't; sorry about that, but we do our best with the source material.") ?>
</p>
<table id="regmem">
<tr><th width="50%"><?= gettext("Removed") ?></th><th width="50%"><?= gettext("Added") ?></th></tr>
<?php
            }
            $name = $m[2];
            $ddata = $m[4];
            if (preg_match('/Nil\./', $ddata)) {
                $nil[$_] = true;
            }
            preg_match_all('#<category type="(.*?)" name="(.*?)">(.*?)</category>#s', $ddata, $mm, PREG_SET_ORDER);
            foreach ($mm as $k => $m) {
                $cat_type = $m[1];
                $cat_name = $m[2];
                $cats[$date][$cat_type] = $cat_name;
                $cat_data = canonicalise_data($m[3]);
                $data[$_][$cat_type] = $cat_data;
            }
        }
    }

    $out = '';
    foreach ($files as $i => $_) {
        if ($_ <= $earliest) {
            break;
        }
        $date_pre = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $_);
        $date_post = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $files[$i + 1]);
        $pretty = format_date($date_pre, LONGDATEFORMAT);
        $oout = '';
        foreach ($data[$_] as $cat_type => $cat_data) {
            $old = array_key_exists($cat_type, $data[$files[$i + 1]]) ? $data[$files[$i + 1]][$cat_type] : '';
            $new = $data[$_][$cat_type];
            if ($diff = clean_diff($old, $new)) {
                $oout .= cat_heading($cat_type, $date_pre, $date_post) . $diff;
            }
        }
        foreach ($data[$files[$i + 1]] as $cat_type => $cat_data) {
            if (array_key_exists($cat_type, $data[$_])) {
                continue;
            }
            if ($diff = clean_diff($data[$files[$i + 1]][$cat_type], '')) {
                $oout .= cat_heading($cat_type, $date_pre, $date_post) . $diff;
            }
        }
        if ($oout) {
            $out .= span_row("<h2>$pretty - <a href=\"./?d=$date_pre#$p\">View full entry</a></h2>", true) . $oout;
        }
    }
    $_ = $earliest;
    $date = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $_);
    $pretty = format_date($date, LONGDATEFORMAT);
    $out .= span_row("<h2>$pretty (" . gettext("first entry we have") . ")</h2>", true);
    if (array_key_exists($_, $nil)) {
        $out .= span_row('Nothing');
    }
    foreach ($data[$_] as $cat_type => $d) {
        $out .= cat_heading($cat_type, '', $date);
        $out .= span_row(prettify($d));
    }
    print $out;
    if ($name) {
        print '</table>';
    }
}

function register_history($f) {
    global $dir, $files, $names, $DATA, $PAGE, $link, $this_page;
    $this_page = 'regmem_diff';
    $new = 0;
    if ($f) {
        $f = "$dir/regmem$f.xml";
        $count = count($files);
        for ($i = 0; $i < $count; ++$i) {
            if ($files[$i] == $f) {
                $new = $i;
                break;
            }
        }
    }
    $old = $new + 1;
    $old = $files[$old];
    $old_iso = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $old);
    $old_pretty = format_date($old_iso, LONGDATEFORMAT);
    $new = $files[$new];
    $new_iso = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $new);
    $new_pretty = format_date($new_iso, LONGDATEFORMAT);
    $old = _load_file($old);
    $new = _load_file($new);

    $DATA->set_page_metadata($this_page, 'heading', 'Changes from ' . $old_pretty . ' to ' . $new_pretty);
    $PAGE->stripe_start();
    print $link;
    $data = [];
    parse_file($old, $old_iso, 'old', $data);
    parse_file($new, $new_iso, 'new', $data);
    ?>
<p>This page shows all the changes in the Register of Members' Interests between the editions of <a href="./?d=<?=$old_iso ?>"><?=$old_pretty ?></a> and <a href="./?d=<?=$new_iso ?>"><?=$new_pretty ?></a>, in alphabetical order by MP.</p>
<table cellpadding="3" cellspacing="0" border="0" id="regmem">
<tr><th width="50%">Removed</th><th width="50%">Added</th></tr>
<?php

        uksort($data, 'by_name_ref');
    foreach ($data as $person_id => $v) {
        $out = '';
        foreach ($v as $cat_type => $vv) {
            $out .= cat_heading($cat_type, $old_iso, $new_iso);
            $old = (array_key_exists('old', $data[$person_id][$cat_type]) ? $data[$person_id][$cat_type]['old'] : '');
            $new = (array_key_exists('new', $data[$person_id][$cat_type]) ? $data[$person_id][$cat_type]['new'] : '');
            $out .= clean_diff($old, $new);
        }
        if ($out) {
            print span_row('<h2>' . $names[$person_id] . ' - <a href="?p=' . $person_id . '">Register history</a> | <a href="/mp/?pid=' . $person_id . '">MP&rsquo;s page</a></h2>', true) . $out;
        }
    }
    print '</table>';
}

function by_name_ref($a, $b) {
    global $names;
    $a = preg_replace('/^.* /', '', $names[$a]);
    $b = preg_replace('/^.* /', '', $names[$b]);
    if ($a > $b) {
        return 1;
    } elseif ($a < $b) {
        return -1;
    }
    return 0;
}

function parse_file($file, $date, $type, &$out) {
    global $cats, $names;
    preg_match_all('#<regmem personid="uk.org.publicwhip/person/(.*?)" (?:memberid="(.*?)" )?membername="(.*?)" date="(.*?)">(.*?)</regmem>#s', $file, $mm, PREG_SET_ORDER);
    foreach ($mm as $k => $m) {
        $person_id = $m[1];
        $name = $m[3];
        $data = $m[5];
        $names[$person_id] = $name;
        preg_match_all('#<category type="(.*?)" name="(.*?)">(.*?)</category>#s', $data, $mmm, PREG_SET_ORDER);
        foreach ($mmm as $k => $m) {
            $cat_type = $m[1];
            $cat_name = $m[2];
            $cats[$date][$cat_type] = $cat_name;
            $cat_data = canonicalise_data($m[3]);
            $out[$person_id][$cat_type][$type] = $cat_data;
            if ($type == 'new' && array_key_exists('old', $out[$person_id][$cat_type]) && $cat_data == $out[$person_id][$cat_type]['old']) {
                unset($out[$person_id][$cat_type]);
            }
        }
    }
}

function _load_file($f) {
    $file = file_get_contents($f);
    preg_match('#encoding="([^"]*)"#', $file, $m);
    $encoding = $m[1];
    if ($encoding == 'ISO-8859-1') {
        $file = @iconv('iso-8859-1', 'utf-8', $file);
    }
    return $file;
}

function front_page() {
    global $files;
    foreach ($files as $_) {
        $file = _load_file($_);
        preg_match_all('#<regmem personid="uk.org.publicwhip/person/(.*?)" (?:memberid="(.*?)" )?membername="(.*?)" date="(.*?)">(.*?)</regmem>#s', $file, $m, PREG_SET_ORDER);
        foreach ($m as $k => $v) {
            $person_id = $v[1];
            $name = $v[3];
            $names[$person_id] = $name;
        }
    }
    $c = 0;
    $year = 0;
    $view = '';
    $compare = '';
    $count = count($files);
    for ($i = 0; $i < $count; ++$i) {
        preg_match('/(\d\d\d\d)-(\d\d-\d\d)/', $files[$i], $m);
        $y = $m[1];
        $md = $m[2];
        if ($c++) {
            $view .= ' | ';
            if ($i < $count - 1) {
                $compare .= ' | ';
            }
        }
        if ($year != $y) {
            $year = $y;
            $view .= "<em>$year</em> ";
            if ($i < $count - 1) {
                $compare .= "<em>$year</em> ";
            }
        }
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        preg_match('/(\d\d)-(\d\d)/', $md, $m);
        $date = ($m[2] + 0) . ' ' . $months[$m[1] - 1];
        $view .= '<a href="./?d=' . $y . '-' . $md . '">' . $date . '</a>';
        if ($i < $count - 1) {
            $compare .= '<a href="?f=' . $y . '-' . $md . '">' . $date . '</a>';
        }
    }
    ?>
<p>This section of the site lets you see how MPs' entries in the Register of Members' Interests have changed over time, either by MP, or for a particular issue of the Register.</p>

<p>The rules concerning what must be registered can be found in the House of Commons&rsquo; Code of Conduct at <a href="http://www.publications.parliament.uk/pa/cm200809/cmcode/735/73504.htm">http://www.publications.parliament.uk/pa/cm200809/cmcode/735/73504.htm</a>.</p>

<p>So, either <strong>pick an issue to compare against the one previous:</strong></p>
<p align="center"><?=$compare ?></p>
<p><strong>View a particular edition of the Register of Members' Interests:</strong></p>
<p align="center"><?=$view ?></p>
<p>Or <strong>view the history of an MP's entry in the Register:</strong></p> <ul id="mps">
<?php
        uasort($names, 'by_name');
    foreach ($names as $_ => $value) {
        print '<li><a href="?p=' . $_ . '">' . $value . '</a>';
    }
    print '</ul>';

}

function show_register($d) {
    global $dir, $files, $names, $PAGE, $DATA, $this_page, $link;
    $d = "$dir/regmem$d.xml";
    if (!in_array($d, $files)) {
        $d = $files[0];
    }
    $d_iso = preg_replace("#$dir/regmem(.*?)\.xml#", '$1', $d);
    $d_pretty = format_date($d_iso, LONGDATEFORMAT);
    $d = _load_file($d);
    $data = [];
    parse_file($d, $d_iso, 'only', $data);
    $this_page = 'regmem_date';
    $DATA->set_page_metadata($this_page, 'heading', "The Register of Members' Interests, $d_pretty");
    ;
    $PAGE->stripe_start();
    print $link;
    ?>
<p>This page shows the Register of Members' Interests as released on <?=$d_pretty ?>, in alphabetical order by MP.
<?php if ($d_iso > '2002-05-14') { ?><a href="./?f=<?=$d_iso ?>">Compare this edition with the one before it</a></p><?php } ?>
<div id="regmem">
<?php
        uksort($data, 'by_name_ref');
    foreach ($data as $person_id => $v) {
        $out = '';
        foreach ($v as $cat_type => $vv) {
            $out .= cat_heading($cat_type, $d_iso, $d_iso, false);
            $d = (array_key_exists('only', $data[$person_id][$cat_type]) ? $data[$person_id][$cat_type]['only'] : '');
            $out .= prettify($d) . "\n";
        }
        if ($out) {
            print '<div class="block">';
            print '<h2><a name="' . $person_id . '"></a>' . $names[$person_id] . ' - ';
            print '<a href="?p=' . $person_id . '">Register history</a> | ';
            print '<a href="/mp/?pid=' . $person_id . '">MP&rsquo;s page</a>';
            print '</h2> <div class="blockbody">';
            print "\n$out";
            print '</div></div>';
        }
    }
    print '</div>';
}

function by_name($a, $b) {
    $a = preg_replace('/^.* /', '', $a);
    $b = preg_replace('/^.* /', '', $b);
    if ($a > $b) {
        return 1;
    } elseif ($a < $b) {
        return -1;
    }
    return 0;
}
function canonicalise_data($cat_data) {
    $cat_data = preg_replace('#^.*?<item#s', '<item', $cat_data);
    $cat_data = str_replace(['<i>', '</i>'], '', $cat_data);
    $cat_data = preg_replace('/<item subcategory="(.*?)">\s*/', '<item>($1) ', $cat_data);
    $cat_data = preg_replace('/<item([^>]*?)>\s*/', '<item>', $cat_data);
    $cat_data = preg_replace('/  +/', ' ', $cat_data);
    $cat_data = preg_replace('# (\d{1,2})th #', ' $1<sup>th</sup> ', $cat_data);
    return $cat_data;
}

function _clean($s) {
    $s = preg_replace("/&(pound|#163);/", "£", $s);
    $s = preg_replace("#</?(span|i|em)( [^>]*)?" . ">#i", '', $s);
    $s = preg_split("/\s*\n\s*/", $s);
    return $s;
}

function clean_diff($old, $new) {
    $old = _clean($old);
    $new = _clean($new);
    $r = array_diff($old, $new);
    $a = array_diff($new, $old);
    if (!count($r) && !count($a)) {
        return '';
    }
    $r = join("\n", $r);
    $r = $r ? '<td class="r"><ul>' . $r . '</ul></td>' : '<td>&nbsp;</td>';
    $a = join("\n", $a);
    $a = $a ? '<td class="a"><ul>' . $a . '</ul></td>' : '<td>&nbsp;</td>';
    $diff = '<tr>' . $r . $a . '</tr>';
    $diff = preg_replace('#<item.*?>(.*?)</item>#', '<li>$1</li>', $diff);
    return $diff;
}

function prettify($s) {
    $s = preg_replace('#<item>(.*?)</item>#', '<li>$1</li>', $s);
    return "<ul>$s</ul>";
}

function cat_heading($cat_type, $date_pre, $date_post, $table = true) {
    global $cats;
    $cat_pre = $cats[$date_pre][$cat_type] ?? '';
    $cat_post = $cats[$date_post][$cat_type] ?? '';
    if ($cat_pre == $cat_post || !$cat_post || !$cat_pre) {
        if (!$cat_pre) {
            $cat_pre = $cat_post;
        }
        $row = "<h3>$cat_type. $cat_pre</h3>";
        if ($table) {
            return "<tr><th colspan=\"2\">$row</th></tr>\n";
        }
        return $row;
    } else {
        if ($table) {
            return "<tr><th><h3>$cat_type. $cat_post</h3></th><th><h3>$cat_type. $cat_pre</h3></th></tr>";
        } else {
            return "<h3>$cat_type. $cat_post / $cat_pre</h3>";
        }
    }
}

function span_row($s, $heading = false) {
    if ($heading) {
        return "<tr><th colspan=\"2\">$s</th></tr>\n";
    }
    return "<tr><td colspan=\"2\">$s</td></tr>\n";
}
