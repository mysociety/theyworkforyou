<div class="full-page__row">

    <div class="business-section">
        <div class="business-section__header">
            <h1 class="business-section__header__title">
                <?= $parent_title ?>
            </h1>
            <p class="business-section__header__date">
                <?= format_date( $info['date'], LONGERDATEFORMAT ) ?>
            </p>
        </div>
      <?php if ( isset($rows) ) { ?>
        <div class="business-section__primary">
            <ul class="business-list">
              <?php
                $prevlevel = '';
                foreach ( $rows as $row ) { ?>
                <?php if ( $row['htype'] == 10 ) {
                    if ( $prevlevel == 'sub' ) { ?>
                    </ul>
                    </li>
                    <?php } elseif ( $prevlevel == 'top' ) { ?>
                    </li>
                    <?php } ?>
                    <li>
                <?php } else {
                    if ( $prevlevel == '' ) { ?>
                    <li>
                    <?php } elseif ( $prevlevel == 'top' ) { ?>
                    <ul>
                    <li>
                    <?php } ?>
                <?php } ?>
                  <?php if ( isset($row['excerpt']) && strstr($row['excerpt'], "was asked&#8212;") ) { ?>
                    <div class="business-list__title">
                        <h3>
                            <?= $row['body'] ?>
                        </h3>
                    </div>
                  <?php } else { ?>
                    <a href="<?= $row['listurl'] ?>" class="business-list__title">
                        <h3>
                            <?= $row['body'] ?>
                        </h3>
                      <?php if ( isset($row['contentcount']) && $row['contentcount'] > 0 ) { ?>
                        <span class="business-list__meta">
                            <?= $row['contentcount'] == 1 ? '1 speech' : $row['contentcount'] . ' speeches' ?>
                        </span>
                      <?php } ?>
                    </a>
                  <?php }
                  if ( isset($row['excerpt']) ) { ?>
                    <p class="business-list__excerpt">
                        <?= trim_characters($row['excerpt'], 0, 200 ) ?>
                    </p>
                  <?php } ?>
                <?php if ( $row['htype'] == 10 ) {
                    $prevlevel = 'top';
                } else {
                    $prevlevel = 'sub'; ?>
                </li>
                <?php } ?>
              <?php } ?>
                <?php if ( $prevlevel == 'sub' ) { ?>
                </ul>
                </li>
                <?php } ?>
            </ul>
        </div>
        <div class="business-section__secondary">
            <div class="business-section__secondary__item">
                <h3>What is this?</h3>
                <?php include '_' . $section . '_desc.php'; ?>
            </div>
            <div class="business-section__secondary__item">
                <?php include '_calendar_section.php'; ?>
            </div>
            <div class="business-section__secondary__item">
                <?php include( dirname(__FILE__) . '/../sidebar/looking_for.php' ); ?>
            </div>
        </div>
      <?php } else { ?>
        <div class="business-section__primary">
            No data to display.
        </div>
      <?php } ?>
    </div>

    <?php $search_title = "Search $title"; include '_search.php'; ?>

</div>
