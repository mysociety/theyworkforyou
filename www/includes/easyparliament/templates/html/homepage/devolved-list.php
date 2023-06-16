<?php 
$nav_items = array (
    array('hansard', 'alldebatesfront', 'mps', 'peers', 'wranswmsfront'),
    array('sp_home', 'sp_home', 'spdebatesfront', 'msps',  'spwransfront'),
    array('wales_home', 'wales_home', 'wales_debates', 'mss', 'welshlanguage'),
    array('ni_home', 'ni_home', 'nioverview', 'mlas'),
);

?>  
    <nav>
    <ul class="homepage-parl-nav">
    <div class="row nested-row">
    <?php
        foreach ($nav_items as $item_list) {
        $top_level = $item_list[0];
        $remaining = array_slice($item_list, 1);
        $menu_data = $DATA->page_metadata($top_level, 'menu');
        $URL = new \MySociety\TheyWorkForYou\Url($top_level);
        $url = $URL->generate();
    ?>
    <div class="homepage-parl-column">
    <li class="top-level-parl">
        <p class="parl-top-link"><?= $menu_data['text'] ?></p>
        <?php if (count($remaining)) { ?>
            <ul>
                <?php foreach ($remaining as $item) {
                if ($item == "welshlanguage") {
                    $menu_data = $DATA->page_metadata('wales_home', 'menu');
                    $menu_data["title"] = "Welsh language / Cymraeg";
                    $URL = new \MySociety\TheyWorkForYou\Url('wales_home');
                    $url = $URL->generate();
                    if (strpos(DOMAIN, 'www') !== false) {
                        $url = "//" . str_replace('www.', 'cy.', DOMAIN) . $url;
                    } else {
                        $url = "//cy." . DOMAIN . $url;
                    }
                } else {
                    $menu_data = $DATA->page_metadata($item, 'menu');
                    $URL = new \MySociety\TheyWorkForYou\Url($item);
                    $url = $URL->generate();
            }
                ?>  
                    <?php if ($item == $top_level) { ?>
                    <li><a href="<?= $url ?>">Homepage</a></li>
                    <?php } else { ?>
                        <li><a href="<?= $url ?>"><?= $menu_data['title'] ?></a></li>
                    <?php } ?>
                <?php } ?>
            </ul>
        <?php } ?>
    </li>
    </div>
    <?php } ?>
    </div>
    </ul>
</nav>