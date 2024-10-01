

<?php if ($this_page == "mp") { ?>

<?php if ($current_member[1]) { ?>

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

<?php } ?>

<div class="panel">
<h2>Support TheyWorkForYou's work in the new Parliament</h2>

    <p>TheyWorkForYou is run by <a href="https://www.mysociety.org/">mySociety</a>, a small UK charity.</p>
    
    <p>We're a very efficient operation and do a lot with a small team. At the moment TheyWorkForYou, which is used by millions of people each year, is run with less than the equivalent of one full-time person.</p>

    <p>If we had <a href="/support-us/">a <strong>bit</strong> more money, we could achieve a <strong>lot</strong> more</a>.</p>
    <p>We want MPs to meet the standards and expectations of the people who elected them - <strong>you</strong>!</p>

    <p>If you share that goal <a href="/support-us/">please donate today</a> to enable greater transparency and accountability of the next government.</p>
    
    <p>Learn more about <a href="/support-us/?utm_source=theyworkforyou.com&utm_content=postcode+donate&utm_medium=link&utm_campaign=postcode&how-much=5">our current work</a>, and <a href="https://www.mysociety.org/democracy/who-funds-them/">our new project WhoFundsThem</a> - looking into MPs’ and APPGs’ financial interests.</p>
                    <div class="inline-donation-box">
                        
                        <a href="/support-us/?utm_source=theyworkforyou.com&utm_content=postcode+donate&utm_medium=link&utm_campaign=postcode&how-much=5#donate-form" class="button" >Donate £5 to TheyWorkForYou</a>
                        <a href="https://www.mysociety.org/democracy/who-funds-them/" class="button">Support our WhoFundsThem campaign</a>

                    </div>
                    <p>Learn more about <a href="/support-us/#why-does-mysociety-need-donations-for-these-sites">how we'll use your donation</a> and <a href="/support-us/#i-want-to-be-a-mysociety-supporter">other ways to help</a>.</p>

</div>
