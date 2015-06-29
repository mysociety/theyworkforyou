<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="full-page__unit">
            <?php if (count($positions) > 0): ?>
            <div class="person-navigation">
                <ul>
                    <li class="active"><a href="<?= $member_url ?>">Overview</a></li>
                    <li><a href="<?= $member_url ?>/votes">Voting Record</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <div class="person-panels">
            <div class="primary-content__unit">

                <?php if (($party == 'Sinn Fein' || $party == utf8_decode('Sinn Féin')) && in_array(HOUSE_TYPE_COMMONS, $houses)): ?>
                <div class="panel">
                    <p>Sinn F&eacute;in MPs do not take their seats in Parliament.</p>
                </div>
                <?php elseif (isset($is_new_mp) && $is_new_mp && count($recent_appearances['appearances']) == 0): ?>
                <div class="panel--secondary">
                    <h3><?= $full_name ?> is a recently elected MP &ndash; elected on <?= format_date($entry_date, LONGDATEFORMAT) ?></h3>

                    <p>When <?= $full_name ?> starts to speak in debates and vote on bills, that information will appear on this page.</p>

                <?php endif; ?>

                <?php if (count($sorted_diffs) > 0): ?>
                <div class="panel">
                    <a name="votes"></a>

                    <?php if (count($positions) > 0): ?>

                        <h2>Policies with a difference</h2>
                        <table>
                            <thead>
                                <td>Policy</td>
                                <td>MP Position</td>
                                <td>Party Position</td>
                                <td></td>
                            </thead>
                          <?php foreach ($sorted_diffs as $policy_id => $diff): ?>
                            <?php if (  $positions[$policy_id]['voted'] != $party_positions[$policy_id]['position'] ) { ?>
                            <tr>
                                <td><?= $policies[$policy_id] ?></td>
                                <td><?= $positions[$policy_id]['voted'] ?></td>
                                <td><?= $party_positions[$policy_id]['position'] ?></td>
                                <td><a href="<?= $member_url?>/divisions?policy=<?= $policy_id ?>">Details</a></td>
                            </tr>
                            <?php } ?>
                          <?php endforeach; ?>
                        </table>

                        <h2>Policies with no difference</h2>
                        <table>
                            <thead>
                                <td>Policy</td>
                                <td>MP Position</td>
                                <td>Party Position</td>
                                <td></td>
                            </thead>
                          <?php foreach ($sorted_diffs as $policy_id => $diff): ?>
                            <?php if (  $positions[$policy_id]['voted'] == $party_positions[$policy_id]['position'] ) { ?>
                            <tr>
                                <td><?= $policies[$policy_id] ?></td>
                                <td><?= $positions[$policy_id]['voted'] ?></td>
                                <td><?= $party_positions[$policy_id]['position'] ?></td>
                                <td><a href="<?= $member_url?>/divisions?policy=<?= $policy_id ?>">Details</a></td>
                            </tr>
                            <?php } ?>
                          <?php endforeach; ?>
                        </table>

                    <?php else: ?>

                        <p>No votes to display.</p>

                    <?php endif; ?>

                </div>
                <?php endif; ?>

                <div class="about-this-page">
                    <div class="about-this-page__one-of-one">
                        <div class="panel--secondary">
                            <p>Please feel free to use the data on this page, but if
                                you do you must cite TheyWorkForYou.com in the body
                                of your articles as the source of any analysis or
                                data you get off this site.</p>

                            <p>This data was produced by TheyWorkForYou from a variety
                                of sources. Voting information from
                                <a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/<?= $member_id ?>&amp;showall=yes">Public Whip</a>.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include INCLUDESPATH . 'easyparliament/templates/research/quant2.php'; ?>
