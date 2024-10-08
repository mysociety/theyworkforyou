<?php

$this_page = '404';
include_once dirname(__FILE__) . '/../includes/easyparliament/init.php';
include_once INCLUDESPATH . 'easyparliament/member.php';

$PAGE->page_start();
$PAGE->stripe_start();

?>

<h2>Page not found</h2>
<h3>Status code: 404</h3>

<p>Sorry, we could not find that page.
We try to maintain old links when a new version of Hansard for a particular
day comes out, but it's possible we missed one.
</p>

<p>If you've come to this page from a link on the site, do let us know so we
can fix it.</p>

<h3>Things to do now</h3>

<ul>
<li>Try using the search box in the top right if you're looking for something
in the data we hold.
<li>The links in the navigation bar above will take you to lists of MPs, Lords,
and so on.
</ul>

<?php

$includes = [
    [
        'type' => 'include',
        'content' => 'whatisthissite',
    ],
];
$PAGE->stripe_end($includes);
$PAGE->page_end();
exit;
