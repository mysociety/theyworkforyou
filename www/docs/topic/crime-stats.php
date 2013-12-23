<?php

include_once "../../includes/easyparliament/init.php";

$NEWPAGE->page_start();
$NEWPAGE->stripe_start('side');

?>

<div class="topic-header">
    <h1>Crime Statistics</h1>
    <h1 class="subheader">&amp; the UK Parliament</h1>

    <p>MPs and Lords often talk about Crime Statistics, because they're a major political issue. Here are some places you might want to start:</p>

</div>

<hr>

<?php

$sidebar = array(
    'type' => 'html',
    'content' => '<div>
        <img src="http://placekitten.com/450/280">
    </div>

    <div>
        <p class="large">We are TheyWorkForYou.com</p>
    </div>

    <div>
        <h4>Give us feedback!</h4>
        <p>How are we doing? Did you find what you were looking for? <a href="#">Drop us a line</a> and let us know!</p>
    </div>'
);

$NEWPAGE->stripe_end(array($sidebar));
$NEWPAGE->page_end();
