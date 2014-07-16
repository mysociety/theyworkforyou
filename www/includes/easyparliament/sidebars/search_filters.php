<?php

function optgroups($data, $current) {
    foreach ($data as $key => $values) {
        echo '<optgroup label="', $key, '">';
        foreach ($values as $k => $v) {
            echo '<option';
            if ($current == $k) echo ' selected';
            echo ' value="', $k, '">', $v;
        }
        echo "</optgroup>\n";
    }
}

global $searchstring;

# XXX If you use a prefix and go to More options ,it doesn't fill the boxes. Need to factor out
# search options into some sort of object that can always return either the parts of the query
# or the long string to actually be used.
$filter_ss = $searchstring;
$from = get_http_var('from');
$to = get_http_var('to');
if (preg_match('#\s*([0-9/.-]*)\.\.([0-9/.-]*)#', $filter_ss, $m)) {
    $from = $m[1];
    $to = $m[2];
    $filter_ss =  preg_replace('#\s*([0-9/.-]*)\.\.([0-9/.-]*)#', '', $filter_ss);
}
$section = get_http_var('section');
if (preg_match('#\s*section:([a-z]*)#', $filter_ss, $m)) {
    $section = $m[1];
    $filter_ss = preg_replace("#\s*section:$section#", '', $filter_ss);
}

$person = trim(get_http_var('person'));
if ($person) {
    $filter_ss = preg_replace('#\s*speaker:[0-9]*#', '', $filter_ss);
}

$this->block_start(array( 'title' => "Filtering your results"));

?>
<form method="get" action="/search/">
<input type="hidden" name="q" value="<?=_htmlspecialchars($filter_ss) ?>">

<ul>

<li><label for="from">Date range:</label><br>
<input type="text" id="from" name="from" value="<?=_htmlspecialchars($from)?>" size="15">
 to <input type="text" name="to" value="<?=_htmlspecialchars($to)?>" size="15">
 <div class="help">
 You can give a <strong>start date, an end date, or both</strong>, to restrict results to a
 particular date range; a missing end date implies the current date, a missing start date
 implies the oldest date we have in the system. Dates can be entered in any format you wish, <strong>e.g.
 &ldquo;3rd March 2007&rdquo; or &ldquo;17/10/1989&rdquo;</strong>.
 </div>

<li>
<label for="person">Person:</label>
<input type="text" name="person" value="<?=_htmlspecialchars($person)?>" size="25">
<div class="help">
Enter a name here to restrict results to contributions only by that person.
</div>

<li>
 <label for="section">Section:</label>
 <select id="section" name="section">
 <option value="">Any
<?php
 optgroups(array(
    'UK Parliament' => array(
        'uk' => 'All UK',
        'debates' => 'House of Commons debates',
        'whall' => 'Westminster Hall debates',
        'lords' => 'House of Lords debates',
        'wrans' => 'Written answers',
        'wms' => 'Written ministerial statements',
        'standing' => 'Bill Committees',
        'future' => 'Future Business',
    ),
    'Northern Ireland Assembly' => array(
        'ni' => 'Debates',
    ),
    'Scottish Parliament' => array(
        'scotland' => 'All Scotland',
        'sp' => 'Debates',
        'spwrans' => 'Written answers',
    ),
 ), $section);
 ?>
 </select>
 <div class="help">
 Restrict results to a particular parliament or assembly that we cover (e.g. the
 Scottish Parliament), or a particular type of data within an institution, such
 as Commons Written Answers.
 </div>

<li><label for="column">Column:</label>
 <input type="text" id="column" name="column" value="" size="10">
 <div class="help">
 If you know the actual column number in Hansard you are interested in (perhaps you&rsquo;re looking up a paper
 reference), you can restrict results to that.
 </div>

</ul>

<p align="right"><input type="submit" value="Go"></p>

</form>

<?php

$this->block_end();
