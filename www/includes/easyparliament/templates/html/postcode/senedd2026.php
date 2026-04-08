
<script type="module" src="https://cdn.jsdelivr.net/npm/@justinribeiro/lite-youtube@1/lite-youtube.min.js"></script>

<h1><?= gettext('Senedd Election') ?></h1>

<p><a href='#current'><?= gettext('See your current representatives') ?></a>

<?php
$election_date = null;
if ($senedd_ballot) {
    # Extract date from election_id like senedd.c.2025-05-07
    if (preg_match('/(\d{4}-\d{2}-\d{2})$/', $senedd_ballot->election_id, $m)) {
        $election_date = $m[1];
    }
}
if ($election_date) {
    $date = strtotime($election_date);
    $formatted_date = date('jS F Y', $date);
    $datediff = ceil(($date - time()) / 86400);
    ?>
<p><?php printf(gettext('There is a Senedd election on <strong>%s</strong>'), $formatted_date); ?>
<?php
    if ($datediff > 1) {
        echo sprintf(ngettext(' (%d day away)', ' (%d days away)', $datediff), $datediff);
    } elseif ($datediff > 0) {
        echo gettext('(tomorrow!)');
    } elseif ($datediff > -86400) {
        echo gettext('(today!)');
    }
}
?>
.

<?php if ($senedd_ballot) { ?>
<p><?php printf(gettext('For this election, you will be in the <strong>%s</strong> constituency.'), $senedd_ballot->post_name); ?>

<h2><?= gettext('This election has a new voting system') ?></h2>
<p><?= gettext('The way you vote has changed. When voting for your Members of the Senedd, you will now vote on one ballot paper, instead of two as previously. You will vote once for a single party or independent candidate, and seats will be allocated using proportional representation. Party candidates will be elected in the order they appear on their party list.') ?></p>

<p><?= gettext('These changes mean that you will have a new constituency. Each constituency will elect six members, and the overall number of Senedd members is increasing from 60 to 96. Senedd elections will happen every four years.') ?></p>

<p><a href="https://senedd.wales/how-we-work/our-role/senedd-election-and-member-changes/"><?= gettext('Learn more about the Senedd changes.') ?></a></p>
<lite-youtube videotitle="How are members of the Senedd elected?" videoid="UrUBv2e4U_w" posterquality="maxresdefault">
  <a class="lite-youtube-fallback" href="https://www.youtube.com/watch?v=UrUBv2e4U_w">Watch on YouTube: "How are members of the Senedd elected?"</a>
</lite-youtube>

<h2><?= gettext('Parties standing in your constituency'); ?><?= !$senedd_ballot->candidates_verified ? ' (not yet finalised or verified)' : '' ?></h2>

<p><?= gettext('The following shows the candidate lists for the different parties standing in your constituency. The order of parties is randomised.') ?></p>
<p><?= gettext('Within a party list, candidates are ordered by their position on the party list, with the first candidate more likely to be elected than the second, and so on.') ?></p>


<?php
// Group candidates by party
$by_party = array_reduce($senedd_ballot->candidates, function ($carry, $c) {
    $carry[$c->party->party_name][] = $c->person->name;
    return $carry;
}, []);

    $parties = array_keys($by_party);
    shuffle($parties);

    foreach ($parties as $party_name) {
        $items = implode('', array_map(fn($n) => '<li>' . htmlspecialchars($n) . '</li>', $by_party[$party_name]));
        echo '<h3>' . htmlspecialchars($party_name) . '</h3><ul>' . $items . '</ul>';
    }
    ?>

<p>
<a href="https://democracyclub.org.uk/"><img width=150 align="right" src="https://static.democracyclub.org.uk/static/dc_theme/images/logo-with-text.png" alt="<?= gettext('Democracy Club') ?>"></a>
<?php printf(gettext('For more information visit <a href="%s">WhoCanIVoteFor</a>.'), $senedd_ballot->wcivf_url); ?>
<?php printf(gettext('This data has been provided by <a href="%s">Democracy Club</a>, thanks to them.'), 'https://democracyclub.org.uk/'); ?>
<?php } ?>