<div class="full-page">
    <div class="full-page__row search-page">

        <?php include 'form.php'; ?>

        <div class="search-page__section search-page__section--results">
            <div class="search-page__section__primary">
            <h2>Who says <em class="current-search-term"><?= $searchstring ?></em> the most?</h2>

            <?php if ( isset($error) ) {
                if ( $error == 'No results' && isset( $house ) && $house != 0 ) { ?>
                <ul class="search-result-display-options">
                    <li>
                        <?php if ( $house == 1 ) { ?>No results for MPs only<?php }
                        else if ( $house == 2 ) { ?>No results for Peers only<?php }
                        else if ( $house == 4 ) { ?>No results for MSPs only<?php }
                        else if ( $house == 3 ) { ?>No results for MLAs only<?php } ?> |
                        <a href="<?= $this_url->generate('html') ?>">Show results for all speakers</a></li>
                </ul>
                <?php } else { ?>
                <p class="search-results-legend"><?= $error ?></p>
                <?php } ?>
            <?php } ?>

            <?php if ( $wtt ) { ?>
                <p><strong>Now, try reading what a couple of these Lords are saying,
                to help you find someone appropriate. When you've found someone,
                hit the "I want to write to this Lord" button on their results page
                to go back to WriteToThem.
                </strong></p>
            <?php } ?>

            <?php if ( isset($speakers) && count($speakers) ) { ?>
                <?php if ( !$wtt ) { ?>
                <ul class="search-result-display-options">
                    <li>Results grouped by person</li>
                    <li><?php if ( $house == 0 ) { ?>Show All<?php } else { ?><a href="<?= $this_url->generate('html') ?>">Show All</a><?php } ?> |
                        <?php if ( $house == 1 ) { ?>MPs only<?php } else { ?><a href="<?= $this_url->generate('html', array('house'=>1)) ?>">MPs only</a><?php } ?> |
                        <?php if ( $house == 2 ) { ?>Peers only<?php } else { ?><a href="<?= $this_url->generate('html', array('house'=>2)) ?>">Lords only</a><?php } ?> |
                        <?php if ( $house == 4 ) { ?>MSPs only<?php } else { ?><a href="<?= $this_url->generate('html', array('house'=>4)) ?>">MSPs only</a><?php } ?> |
                        <?php if ( $house == 3 ) { ?>MLAs only<?php } else { ?><a href="<?= $this_url->generate('html', array('house'=>3)) ?>">MLAs only</a><?php } ?></li>
                    <li><a href="<?= $ungrouped_url->generate() ?>">Ungroup results</a></li>
                </ul>

                <p class="search-results-legend">The <?= isset($limit_reached) ? '5000 ' : '' ?>most recent mentions of the exact phrase <em class="current-search-term"><?= $searchstring ?></em>, grouped by speaker name.</p>
                <?php } ?>

                <table class="search-results-grouped">
                    <thead>
                        <tr>
                            <th>Occurences</th>
                            <th>Speaker</th>
                            <th>Date range</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ( $speakers as $pid => $speaker ) { ?>
                        <?php if ( $wtt && $pid == 0 ) { continue; } // skip heading count for WTT lords list ?>
                        <tr>
                            <td><?= $speaker['count'] ?></td>
                            <td>
                                <?php if ( $pid ) { ?>
                                    <?php if ( !$wtt || $speaker['left'] == '9999-12-31' ) { ?>
                                    <a href="/search/?s=<?= _htmlentities( $searchstring ) ?>&amp;pid=<?= $pid ?><?= isset($wtt) && $speaker['left'] == '9999-12-31' ? '&amp;wtt=2' : '' ?>">
                                    <?php } ?>
                                    <?= isset($speaker['name']) ? $speaker['name'] : 'N/A' ?>
                                    <?php if ( !$wtt || $speaker['left'] == '9999-12-31' ) { ?>
                                    </a>
                                    <?php } ?>
                                <span class="search-results-grouped__speaker-party">(<?= $speaker['party'] ?>)</span>
                                <?php if ( $house != 2 ) { ?>
                                <?= isset($speaker['office']) ? ' - ' . join('; ', $speaker['office']) : '' ?>
                                <?php } ?>
                                <?php } else { ?>
                                <?= $speaker['name'] ?>
                                <?php } ?>
                            </td>
                            <td>
                            <?php if ( format_date($speaker['pmindate'], 'M Y') == format_date($speaker['pmaxdate'], 'M Y') ) { ?>
                            <?= format_date($speaker['pmindate'], 'M Y') ?>
                            <?php } else { ?>
                            <?= format_date($speaker['pmindate'], 'M Y') ?>&nbsp;&ndash;&nbsp;<?= format_date($speaker['pmaxdate'], 'M Y') ?>
                            <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
            </div>

            <?php include 'sidebar.php' ?>
        </div>

    </div>
</div>
