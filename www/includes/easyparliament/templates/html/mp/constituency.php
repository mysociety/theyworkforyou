<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/header.php";
?>

<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <?php include '_person_navigation.php'; ?>
                    <?php include '_featured_content.php'; ?>
                    <?php include '_donation.php'; ?>
                </div>
            </div>

            <div class="primary-content__unit">
                <div class="panel">
                    <h2 id="constituency-info">Learn more about <?= $constituency ?></h2>
                    
                    <div class="constituency-info-content">
                        <div class="local-hub-image">
                            <img src="https://www.localintelligencehub.com/static/img/opengraph.c0c844c29b41.png" 
                                 alt="Local Intelligence Hub - Understanding your local area" 
                                 style="max-width: 100%; height: auto; margin: 20px 0; border-radius: 8px;">
                        </div>
                        
                        <p>Get detailed local information about <?= $constituency ?> from mySociety's <a href="https://www.localintelligencehub.com/" target="_blank" rel="noopener noreferrer">Local Intelligence Hub</a>.</p>
                        
                        <p>The <a href="https://www.localintelligencehub.com/" target="_blank" rel="noopener noreferrer">Local Intelligence Hub</a> is your starting point for data about local MPs, constituencies, public opinion and the climate and nature movement. It provides:</p>
                        <ul style="list-style-type: disc; padding-left: 20px;">
                            <li>Local demographic and economic data</li>
                            <li>Analysis of public opinion in your area</li>
                            <li>Information about the climate and nature movement</li>
                            <li>Tools for community groups and individual campaigners</li>
                            <li>Data to support informed conversations with political representatives</li>
                        </ul>
                        
                        <div class="constituency-hub-cta">                            
                            <a href="https://www.localintelligencehub.com/area/WMC23/<?= urlencode($constituency) ?>" 
                               class="button button--primary button--large" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                Visit Local Intelligence Hub for <?= $constituency ?>
                            </a>
                        </div>
                        
                        <div class="about-local-hub">
                            <h3>About the Local Intelligence Hub</h3>
                            <p>The <a href="https://www.localintelligencehub.com/" target="_blank" rel="noopener noreferrer">Local Intelligence Hub</a> is a platform to analyse and explore data about local MPs, constituencies, public opinion and the climate and nature movement.</p>
                            
                            <p>The Local Intelligence Hub supports national campaigning as well as local organising by community groups and individual campaigners. It enables community groups and individual campaigners to have informed conversations with their local political representatives or candidates for election, in order to demonstrate the diversity and scale of the public mandate for action on climate and nature in communities across the UK.</p>
                            
                            <p>Whether you're a resident, journalist, campaigner, or just curious about local affairs, the Hub provides the information you need to understand your community better.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once INCLUDESPATH . "easyparliament/templates/html/mp/_profile_footer.php";
?>