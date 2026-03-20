
<h1>Scottish Parliament Election</h1>

<p><a href='#current'>See your current representatives</a>

<?php
$election_date = null;
if (isset($sp_ballots['constituency'])) {
    # Extract date from election_id like sp.c.2025-05-07
    if (preg_match('/(\d{4}-\d{2}-\d{2})$/', $sp_ballots['constituency']->election_id, $m)) {
        $election_date = $m[1];
    }
}
if ($election_date) {
    $date = strtotime($election_date);
    $formatted_date = date('jS F Y', $date);
    $datediff = ceil(($date - time()) / 86400);
    ?>
<p>There is a Scottish Parliament election on <strong><?= $formatted_date ?></strong>
<?php
        if ($datediff > 1) {
            echo "($datediff days away)";
        } elseif ($datediff > 0) {
            echo '(tomorrow!)';
        } elseif ($datediff > -86400) {
            echo '(today!)';
        }
}
?>
.

<?php if (isset($sp_ballots['constituency'])) { ?>
<h2>Constituency Vote</h2>
<p>For the constituency vote, you will be in the
<strong><?= $sp_ballots['constituency']->post_name ?></strong>
constituency.

<p>
The people standing in your constituency
<?php
if (!$sp_ballots['constituency']->candidates_verified) {
    echo '(not yet finalised or verified)';
}
    ?> are:

<ul>
<?php foreach ($sp_ballots['constituency']->candidates as $candidate) {
    echo '<li>';
    echo htmlspecialchars($candidate->person->name);
    echo ' (' . htmlspecialchars($candidate->party->party_name) . ')';
    echo '</li>';
}
    ?>
</ul>

<p>For more information visit <a href="<?= $sp_ballots['constituency']->wcivf_url ?>">WhoCanIVoteFor</a>.
<?php } ?>

<?php if (isset($sp_ballots['regional'])) { ?>
<h2>Regional List Vote</h2>
<p>For the regional list vote, you will be in the
<strong><?= $sp_ballots['regional']->post_name ?></strong>
region.

<p>
The parties and candidates standing in your region
<?php
if (!$sp_ballots['regional']->candidates_verified) {
    echo '(not yet finalised or verified)';
}
    ?> are:

<?php
$by_party = array_reduce($sp_ballots['regional']->candidates, function ($carry, $candidate) {
    $party_name = $candidate->party->party_name;
    $carry[$party_name][] = $candidate;
    return $carry;
}, []);

    $parties = array_keys($by_party);
    shuffle($parties);

    foreach ($parties as $party_name) {
        echo '<h4>' . htmlspecialchars($party_name) . '</h4><ul>';
        foreach ($by_party[$party_name] as $candidate) {
            echo '<li>' . htmlspecialchars($candidate->person->name);
            if (isset($candidate->party_list_position)) {
                echo ' - List position ' . htmlspecialchars((string) $candidate->party_list_position);
            }
            echo '</li>';
        }
        echo '</ul>';
    }
    ?>

<p>For more information visit <a href="<?= $sp_ballots['regional']->wcivf_url ?>">WhoCanIVoteFor</a>.
<?php } ?>

<p>
<a href="https://democracyclub.org.uk/"><img width=150 align="right" src="https://static.democracyclub.org.uk/static/dc_theme/images/logo-with-text.png" alt="Democracy Club"></a>
This data has been provided by <a href="https://democracyclub.org.uk/">Democracy Club</a>, thanks to them.
</p>
