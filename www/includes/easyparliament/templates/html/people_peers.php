<?php

/*
Used on the 'All Peers' page to produce the list of Peers.

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

$URL = new \MySociety\TheyWorkForYou\Url($this_page);

if ($order == 'name') {
    $th_name = 'Name';
} else {
    $URL->insert(array('o'=>'n'));
    $th_name = '<a href="'. $URL->generate() .'">Name</a>';
}
$URL->insert(array('o'=>'p'));
$th_party = '<a href="' . $URL->generate() . '">Party</a>';

if ($order == 'party')
    $th_party = 'Party';

?>
                <div class="sort">
                    Sort by:
                    <ul>
                        <li><?php echo $th_name; ?> |</li>
                        <li><?php echo $th_party; ?></li>
                    </ul>
                </div>
                <table class="people">
                <thead>
                <tr>
                <th colspan="2">Name</th>
                <th>party</th>
                <th>Ministerialship</th>
                </tr>
                </thead>
                <tbody>
<?php

$URL = new \MySociety\TheyWorkForYou\Url(str_replace('s', '', $this_page));
$style = '2';

foreach ($data['data'] as $pid => $peer) {
    render_peers_row($peer, $style, $order, $URL);
}
?>
                </tbody>
                </table>

<?php

function manymins($p, $d) {
    return prettify_office($p, $d);
}

function render_peers_row($peer, &$style, $order, $URL) {
    global $parties;

    // Stripes
    $style = $style == '1' ? '2' : '1';

    $name = member_full_name(2, $peer['title'], $peer['first_name'], $peer['last_name'], $peer['constituency']);
    if (array_key_exists($peer['party'], $parties))
        $party = $parties[$peer['party']];
    else
        $party = $peer['party'];

#	$MPURL->insert(array('pid'=>$peer['person_id']));
    ?>
            <tr>
                <td class="row">
                <?php
                list($image,$sz) = MySociety\TheyWorkForYou\Utility\Member::findMemberImage($peer['person_id'], true, 'lord');
                if ($image) {
                    echo '<a href="' . $URL->generate().make_member_url($name, null, 2, $peer['person_id']) . '" class="speakerimage"><img height="59" alt="" src="', $image, '"';
                    echo '></a>';
                }
                ?>
                </td>
                <td class="row-<?php echo $style; ?>"><a href="<?php echo $URL->generate().make_member_url($name, null, 2, $peer['person_id']); ?>"><?php echo ucfirst($name); ?></a></td>
                <td class="row-<?php echo $style; ?>"><?php echo $party; ?></td>
                <td class="row-<?php echo $style; ?>"><?php
    if (is_array($peer['dept'])) print join('<br>', array_map('manymins', $peer['pos'], $peer['dept']));
    elseif ($peer['dept']) print prettify_office($peer['pos'], $peer['dept']);
    else print '&nbsp;'
?></td>

            </tr>
<?php

}
