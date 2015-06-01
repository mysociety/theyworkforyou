<div class="full-page">
    <div class="full-page__row search-page">

        <?php include 'form.php'; ?>

        <?php if ( $searchstring ) { ?>
        <div class="search-page__section search-page__section--results">
            <div class="search-page__section__primary">
                <?php if ( $cons ) { ?>
                    <?php if ( count($cons) > 1 ) { ?>
                    <h2>MPs in constituencies matching <em class="current-search-term"><?= $info['s'] ?></em></h2>
                    <?php } else { ?>
                    <h2>MP for <em class="current-search-term"><?= $info['s'] ?></em></h2>
                    <?php } ?>
                    <?php foreach ( $cons as $member ) { ?>
                        <?php include('person.php'); ?>
                    <?php } ?>
                <?php } ?>

                <?php if ( $members ) { ?>
                <h2>People matching <em class="current-search-term"><?= $info['s'] ?></em></h2>

                <?php foreach ( $members as $member ) { ?>
                    <?php include('person.php'); ?>
                <?php } ?>

                <hr>
                <?php } ?>

                <h2>
                <?php if ( $pagination_links ) { ?>
                Results <?= $pagination_links['first_result'] ?>&ndash;<?= $pagination_links['last_result'] ?> of <?= $info['total_results'] ?>
                <?php } else if ( $info['total_results'] == 1 ) { ?>
                The only result
                <?php } else if ( $info['total_results'] == 0 ) { ?>
                There were no results
                <?php } else { ?>
                All <?= $info['total_results'] ?> results
                <?php } ?>
                for <em class="current-search-term"><?= _htmlentities($info['s']) ?></em></h2>

                <?php if ( $info['total_results'] ) { ?>
                <ul class="search-result-display-options">
                    <li>Sorted by relevance</li>
                    <li>Sort by date: <a href="#">newest</a> / <a href="#">oldest</a></li>
                    <li><a href="/search/?q=<?= $searchstring ?>&amp;o=p">Group by person</a></li>
                </ul>
                <?php } ?>

                <?php foreach ( $rows as $result ) { ?>
                <div class="search-result search-result--generic">
                <h3 class="search-result__title"><a href="<?= $result['listurl'] ?>"><?= $result['parent']['body'] ?></a> (<?= format_date($result['hdate'], SHORTDATEFORMAT) ?>)</h3>
                    <p class="search-result__description"><?= isset($result['speaker']) ? $result['speaker']['name'] . ': ' : '' ?><?= $result['extract'] ?></p>
                </div>
                <?php } ?>

                <hr>

                <?php if ( $pagination_links ) { ?>
                <div class="search-result-pagination">
                    <?php if ( isset($pagination_links['prev']) ) { ?>
                    <a href="<?= $pagination_links['firstpage']['url'] ?>">&lt;&lt;</a>
                    <a href="<?= $pagination_links['prev']['url'] ?>">&lt;</a>
                    <?php }
                    foreach ( $pagination_links['nums'] as $link ) { ?>
                    <a href="<?= $link['url'] ?>"<?= $link['current'] ? ' class="search-result-pagination__current-page"' : '' ?>><?= $link['page'] ?></a>
                    <?php }
                    if ( isset($pagination_links['next']) ) { ?>
                    <a href="<?= $pagination_links['next']['url'] ?>">&gt;</a>
                    <a href="<?= $pagination_links['lastpage']['url'] ?>">&gt;&gt;</a>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>

            <?php include 'sidebar.php' ?>
        </div>
        <?php } ?>

    </div>
</div>

<script type="text/javascript">
$(function(){
  $('.js-toggle-search-options').on('click', function(e){
    e.preventDefault();
    var id = $(this).attr('href');
    if($(id).is(':visible')){
      $('.js-toggle-search-options[href="' + id + '"]').removeClass('toggled');
      $(id).find(':input').attr('disabled', 'disabled');
      $(id).slideUp(250);
    } else {
      $('.js-toggle-search-options[href="' + id + '"]').addClass('toggled');
      $(id).find(':input:disabled').removeAttr('disabled');
      $(id).slideDown(250);
    }
  });
  <?= $is_adv ? '' : '$("#options").find(":input").attr("disabled", "disabled");' ?>

  $( $('.js-toggle-search-options').attr('href') ).hide();
});
</script>
