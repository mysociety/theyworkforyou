<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<style>
table {
  border-collapse: collapse;
}

td, th {
  max-width: 200px;
  word-wrap: break-word;
overflow: hidden;
word-wrap: break-word;
white-space: normal;
}

td.wrap {
  white-space: normal;
}
</style>

<?php

function currency($amount) {
    return '£' . (intval($amount) == $amount ? number_format($amount, 0) : number_format($amount, 2));
}

function humInt(int $num): string {
    $words = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];

    return ($num >= 0 && $num < 10) ? $words[$num] : strval($num);
}

function asDf($value) {
    return $value;
}

?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <?php include '_person_navigation.php'; ?>
        </div>
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>

                <h3 class="browse-content"><?= gettext('Browse content') ?></h3>
                <ul>
                        <li><a href="https://pages.mysociety.org/parl_register_interests/datasets/parliament_2024/latest">About WhoFundsThem</a></li>
                    </ul>                
                    <?php $election_registers = [$register_2024_enriched]; ?>
                    <?php foreach ($election_registers as $register): ?>
                    <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Person $register */ ?>

                    <h3 class="browse-content"><?= $register->displayChamber() ?></h3>
                    <ul>
                            <?php foreach ($register->categories as $category): ?>
                                <?php if ($category->only_null_entries()): ?>
                                    <?php continue; ?>
                                <?php endif; ?>
                                <li><a href="#category-<?= $register->chamber . $category->category_id ?>"><?= $category->category_name ?></a></li>
                            <?php endforeach; ?>
                    </ul>
                    <?php endforeach; ?>

                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">

                <?php if ($register_interests): ?>

                    <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Person $register */ ?>
                    <?php foreach ($election_registers as $register): ?>                        
                    <div class="panel register">
                    <a name="register"></a>
                    <h2>Enriched Election Register</h2>
                    
                        <p>This is an enriched version of the post-election September 2024 register of members' interests for any donations/support or gifts declared.</p>
                        We have worked with a group of volunteers to add additional context to the register, including:
                        <ul style="list-style-type: disc; padding-left: 20px;">
                            <li>Adding short descriptions/urls of organisations</li>
                            <li>Grouping organisations into categories</li>
                            <li>Identifying donors who have given to multiple MPs</li>
                            </ul>
                        <p>This is an experiment in new ways of summarising, enhancing and displaying the register. <a href="<?= $member_url ?>/register">View the standard version of the register</a>.</p>
                        <p>Read our <a href="http://research.mysociety.org/html/beyond-transparency/">Beyond Transparency report</a> for more information about the register and our recommendations for improving it.</p>
                    </div>


                        <?php if ($register->categories->isEmpty()): ?>
                            <div class="panel register">
                                <p><?= ucfirst($full_name) ?> did not declare any relevant donations or gifts in the September 2024 register. </p>
                                <p>This means he did not declare any donations above £1,500 in value, or any gifts above £300 in value.</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($register->categories as $category): ?>
                            <div class="panel register">    
                            <h2 id="category-<?= $register->chamber . $category->category_id ?>"><?= $category->category_name ?></h2>


                            <?php $summary = $category->summary_details("enriched_info"); ?>
                            <h3>Summary</h3>

                                <?php if ($summary["category_id"] == "2"): ?>
                                <p>Donations need to be declared if they are more than £1,500 in value (or if multiple donations of over £500 from the same source add up to more than £1,500) </p>
                                <p>As such, we can only show the breakdown of donations we know about. One of our recommendations is that MPs should declare an aggregate summary of donations below the threshold to fill in the context of large donations. </p>
                                <hr/>
                                <p>As of the September 2024 register, <?= ucfirst($full_name) ?> had declared <?= humInt($summary["items_count"]) ?> <?= make_plural("donation", $summary["items_count"]) ?> of money or support.</p>

                                <?php elseif ($summary["category_id"] == "3"): ?>
                                <p>Donations need to be declared if they are more than £300 in value (or are multiple benefits that add up to £300).</p>
                                <p>One of our recommendations is to adopt a disclosure threshold in line with wider public sector / civil service thresholds (e.g. £20), and to consult and adopt new rules and guidance on when MPs should not accept gifts. </p>
                                <hr/>
                                <p>As of the September 2024 register <?= ucfirst($full_name) ?> had declared <?= humInt($summary["items_count"]) ?> <?= make_plural("gift", $summary["items_count"]) ?>.</p>
                                <?php endif; ?>
                                
                                <p> Of these, <?= currency($summary["in_kind_sum"]) ?> was in kind, and <?= currency($summary["cash_sum"]) ?> was cash. </p>

                                <?php if (!($summary["single_mp_sum"] ?? null)): ?>
                                <p>In-kind <?= strtolower($summary["item_name"]) ?> are <?= strtolower($summary["item_name"]) ?> of goods or services, rather than cash.</p>
                                <?php endif; ?>

                                <p> <?= currency($summary["individual_income"]) ?> came from private individuals, and <?= currency($summary["non_individual_income"]) ?> came from other sources.</p>

                                <?php if (isset($summary["single_mp_sum"])): ?>

                                <p> <?= currency($summary["single_mp_sum"]) ?> came from private individuals unique to this MP and <?= currency($summary["multi_mp_sum"]) ?> came from private individuals who had also given to other MPs. </p>
                                
                                <?php endif; ?>
                                <h3>Source of <?= strtolower($summary["item_name"]) ?></h3>

                                <p>The following table shows the split between the sources of <?= strtolower($summary["item_name"]) ?>, and whether they are cash or in-kind <?= strtolower($summary["item_name"]) ?>.</p>


                                <?= $summary["source_pivot"] ?>

                                <h3><?= $summary["item_name"] ?> from organisations</h3>

                                <?php if ($summary["org_table"] ?? null): ?>

                                <p>We have grouped organisations into a set of common categories.</p>
                                    
                                <p>Here is a summary of the kinds of organisations that donated to <?= ucfirst($full_name) ?>.</p>

                                <?= $summary["org_group_pivot"] ?>

                                <h4>Details</h4>
                                <?= $summary["org_table"] ?>
                                <?php else: ?>

                                <p>No <?= strtolower($summary["item_name"]) ?> were from organisations.</p>

                                <?php endif; ?>

                                <h3><?= $summary["item_name"] ?> from individuals</h3>

                                <?php if ($summary["multi_pivot"] ?? null): ?>
                                <p>Most donors only donate to a single MP, but a small number of donors donate to multiple MPs.</p>
                                <p>Here is a summary of the <?= $summary["item_name"] ?> from individuals to <?= ucfirst($full_name) ?>.</p>

                                <?= $summary["multi_pivot"] ?>

                                <h4>Details</h4>

                                <?= $summary["individual_table"] ?>

                                <?php else: ?>

                                <p>No registered <?= strtolower($summary["item_name"]) ?> were from individuals.</p>

                                <?php endif; ?>



                                </div>
                        
                        <?php endforeach; ?>
                    
                    <?php endforeach; ?>


                <?php endif; ?>

                <?php include('_profile_footer.php'); ?>

            </div>
        </div>
    </div>
</div>
