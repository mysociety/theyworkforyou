<div class="full-page__row">

    <?php $content = $wrans;
    $title = "Written Answers";
    include '_business_section.php'; ?>
    <?php $search_title = 'Search Written Answers and Written Ministerial Statements';
    include '_search.php'; ?>
    <?php $urls['day'] = $urls['wmsday'];
    $section = 'wms';
    $content = $wms;
    $title = "Written Ministerial Statements";
    include '_business_section.php'; ?>

</div>
