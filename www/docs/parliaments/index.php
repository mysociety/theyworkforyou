<?php

$this_page = "parliaments";

include_once "../../includes/easyparliament/init.php";

$PAGE->page_start();

$PAGE->stripe_start();

$assembly_links = $PAGE->get_menu_links(array ('home', 'sp_home', 'ni_home', 'wales_home'));		

?>

<ul>
    <?php
        foreach ($assembly_links as $assembly_link) {
            echo '<li>' . $assembly_link . '</li>';
        }
    ?>
</ul>

<?php
$PAGE->stripe_end();

$PAGE->page_end();


?>