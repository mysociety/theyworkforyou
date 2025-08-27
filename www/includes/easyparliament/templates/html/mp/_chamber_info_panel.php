

<?php if ($this_page == "mp") { ?>

<?php if ($current_member[1]) { ?>

<div class="grid-overview panel">
    <div class="grid-overview--item">
        <div class="item-header">
            <h2 class="item-heading">Voting Summary</h2>
            <a class="button small" href="/mp/10001/diane_abbott/hackney_north_and_stoke_newington/votes">More voting summary</a>
        </div>
        <div>
            <ul class="vote-descriptions">
                <li class="vote-description" data-policy-id="1030" data-policy-group="environment" data-policy-direction="0.111111" data-policy-party-name="labour" data-policy-party-direction="0.0955952" data-policy-party-score-distance="0.0155158 ?>">

                    Almost always voted for measures to <b>prevent climate change</b><span class="badge badge-environment">Environmental issues</span>
                    <a class="vote-description__source" href="https://votes.theyworkforyou.com/person/10001/policies/commons/labour/all_time/1030">Show votes</a>
                    <a class="vote-description__evidence" href="https://votes.theyworkforyou.com/person/10001/policies/commons/labour/all_time/1030">
                        23 votes for, 3 votes against, 1 agreement, 7 absences, between 2000 and 2025.                    Comparable Labour MPs almost always voted for.
                    </a>
                </li>


                <li class="vote-description" data-policy-id="6693" data-policy-group="environment" data-policy-direction="1" data-policy-party-name="labour" data-policy-party-direction="1" data-policy-party-score-distance="0 ?>">

                    Consistently voted against lower taxes on <b>fuel for motor vehicles</b><span class="badge badge-health">Health</span>
                    <a class="vote-description__source" href="https://votes.theyworkforyou.com/person/10001/policies/commons/labour/all_time/6693">Show votes</a>
                    <a class="vote-description__evidence" href="https://votes.theyworkforyou.com/person/10001/policies/commons/labour/all_time/6693">
                        6 votes against, between 2010 and 2021.                    Comparable Labour MPs consistently voted against.
                    </a>
                </li>


                <li class="vote-description" data-policy-id="6699" data-policy-group="environment" data-policy-direction="0.84" data-policy-party-name="labour" data-policy-party-direction="0.84" data-policy-party-score-distance="0 ?>">

                    Generally voted against higher <b>taxes on plane tickets</b><span class="badge badge-transport">transport</span>
                    <a class="vote-description__source" href="https://votes.theyworkforyou.com/person/10001/policies/commons/labour/all_time/6699">Show votes</a>
                    <a class="vote-description__evidence" href="https://votes.theyworkforyou.com/person/10001/policies/commons/labour/all_time/6699">
                        3 votes against, 6 absences, between 2012 and 2017.                    Comparable Labour MPs generally voted against.
                    </a>
                </li>
        </div>

    </div>
</div>

<style>
    .badge {
        background-color: #e9c4c4;
        font-size: 0.7rem;
        padding: 0.15rem 0.5rem;
        border-radius: 0.5rem;
        margin-left: 0.5rem;
    }
    .badge-transport {
        background-color: #b3ddf3ff;
    }
    .badge-environment {
        background-color: #dee9c4ff;
    }
    .badge-health {
        background-color: #d8c4e9ff;
    }

    .item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .item-header .button {
        text-decoration: none !important;
    }

    @media (min-width: 48em) {
        .panel {
            padding: 2em;
        }
    }

    .donation-cta {
        padding: 1rem 0;
        background-color: #aedba796;
        position: fixed;
        bottom: 0;
        width: 100dvw;
        left: 0;
        backdrop-filter: blur(5px);

        .full-page__row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
        }

        .button {
            margin-bottom: 0;
        }
    }
</style>


<div class="panel">
    <h2>About your Member of Parliament</h2>
    <p>
    Your MP (<?= ucfirst($full_name) ?>) represents you, and all of the people who live in <?= $latest_membership['constituency'] ?>,
        at the UK Parliament in Westminster.
    </p>
    <p>
    MPs split their time between Parliament and their constituency.
        In Parliament, they debate and vote on new laws, review existing laws, and question the Government.
        In the constituency, their focus is on supporting local people and championing local issues.
        They have a small staff team who help with casework, maintain their diaries, and monitor their inbox.
    </p>

<?php } else { ?>

<div class="panel">
    <h2>About your former Member of Parliament</h2>
    <p>
     <?= ucfirst($full_name) ?> is a former MP for <?= $latest_membership['constituency'] ?>.
    </p>
<?php } ?>

    <h2>
    What you can do
    </h2>
    <ul class="rep-actions">
        <li>Find out <a href="#profile">more about your MP</a>, including <a href="<?= $member_url ?>/votes">their voting summary</a><?php if ($register_interests) { ?>, <a href="<?= $member_url ?>/register">register of interests</a><?php } ?> and <a href="#appearances">recent speeches</a>.</li>
    <?php if ($current_member[1]) { ?>
        <li><a href="https://www.writetothem.com/">Write to your MP</a>, or find out about your other local representatives <a href="https://www.writetothem.com">on WriteToThem.com</a>.</li>
    <?php } ?>
    <?php if ($latest_membership['end_date'] == '2024-05-30') { ?>
        <li>Find out more about <a href="https://www.localintelligencehub.com/area/WMC/<?= $latest_membership['constituency'] ?>"><?= $latest_membership['constituency'] ?></a> on the <a href="https://www.localintelligencehub.com/">Local Intelligence Hub</a>.</li>
    <?php } elseif ($current_member[1]) { ?>
        <li>Find out more about <a href="https://www.localintelligencehub.com/area/WMC23/<?= $latest_membership['constituency'] ?>"><?= $latest_membership['constituency'] ?></a> on the <a href="https://www.localintelligencehub.com/">Local Intelligence Hub</a>.</li>
    <?php } ?>
    </ul>
</div>

<div class="donation-cta">
    <div class="full-page__row">
            <span>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod</span>
            <a href="" class="button">Donate</a>
    </div>
</div>

<?php } ?>