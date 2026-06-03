<div class="full-page">
    <div class="full-page__row">
        <div class="person-panels">
            <div class="sidebar__unit in-page-nav">
                <div>
                    <?php if ($show_menu) { ?>
                    <div class="page-mobile-navigation-controller">
                        <button class="js-table-content-button" aria-label="open/close navigation subpages">
                            <i class="fi-list"></i><?= sprintf(gettext('%s Menu'), $page_title) ?>
                        </button>   
                    </div>
                    <div class="js-table-of-content-markdown js-table-of-content table-of-content"></div>
                    <?php } ?>
                </div>
            </div>

            <div class="primary-content__unit">
                <div class="panel">
                    <?= $html ?>
                </div>
            </div>
        </div>
    </div>
</div>
