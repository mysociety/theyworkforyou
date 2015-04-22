<?php

# Used on the 'All MLAs' page to produce the list of MLAs.

global $this_page;

twfy_debug("TEMPLATE", "people_msps.php");

$order = $data['info']['order'];

$URL = new URL($this_page);

if ($order == 'given_name') {
    $th_given_name = 'First name';
} else {
    $URL->insert(array('o'=>'f'));
    $th_given_name = '<a href="'. $URL->generate() .'">First name</a>';
}

if ($order == 'family_name') {
    $th_family_name = 'Last name';
} else {
    $URL->insert(array('o'=>'l'));
    $th_family_name = '<a href="' . $URL->generate() . '">Last name</a>';
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

<?php
} else {

global $THEUSER;
?>

<form action="/postcode/" method="get">
<p><strong>Looking for your <acronym title="Members of the Scottish Parliament">MSP</acronym>, <acronym title="Member of Parliament">MP</acronym> or
<acronym title="Members of the (Northern Irish) Legislative Assembly">MLA</acronym>?</strong><br>
<label for="pc">Enter your UK postcode here:</label>&nbsp; <input type="text" name="pc" id="pc" size="8" maxlength="10" value="<?php echo _htmlentities($THEUSER->postcode()); ?>" class="text">&nbsp;&nbsp;<input type="submit" value=" Go " class="submit"></p>
</form>

<div class="sort">
    Sort by:
    <ul>
        <li><?php echo $th_family_name; ?> |</li>
        <li><?php echo $th_given_name; ?> |</li>
        <li><?php echo $th_party; ?></li>
    </ul>
</div>
<?php
    if ($order == 'family_name' || $order == 'given_name') {
        echo('<div class="sort">');
        for ($i = 65; $i <= 90; $i++) {
            $c = chr($i);
            echo( '<a href="#' . $c . '">' . $c . '</a> ' );
        }
        echo('</div>');
    }
?>

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
        $current_letter = 'A';
        foreach ($data['data'] as $pid => $mp) {
            $letter = '';
            if (strtoupper($mp[$order][0]) != $current_letter) {
                $current_letter = strtoupper($mp[$order][0]);
                $letter = $current_letter;
            }
            render_mps_row($mp, $style, $order, $MPURL, $letter);
    }
?>
</tbody>
</table>
<?php

}

function render_mps_row($mp, &$style, $order, $MPURL, $letter='') {
    $style = $style == '1' ? '2' : '1';
    ?>
<tr>
    <td class="row">
    <?php
        if ($letter) {
            echo '<a name="' . $letter . '"></a>';
        }
    list($image,$sz) = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($mp['person_id'], true, true);
    if ($image) {
        echo '<a href="' . $MPURL->generate() . $mp['url'] . '" class="speakerimage"><img height="59" alt="" src="', $image, '"';
        echo '></a>';
    }
    ?>
    </td>
<td class="row-<?php echo $style; ?>"><a href="<?php
    echo $MPURL->generate() . $mp['url'];
?>"><?php echo $mp['name']; ?></a></td>
<td class="row-<?php echo $style; ?>"><?php echo $mp['party']; ?></td>
<td class="row-<?php echo $style; ?>"><?php echo $mp['constituency']; ?></td>
</tr>
<?php

}

?>
