                          <?php if ($featured['featured']) { ?>
                            <h2>In the news</h2>
                            <?php if (isset($featured['headline'])) { ?>
                            <h3 class="in-the-news__topic"><a href="<?= $featured['list_url'] ?>"><?= $featured['headline'] ?></a></h3>
                            <?php if (isset($featured['context'])) { ?>
                            <p class="in-the-news__context"><?= $featured['context'] ?></p>
                            <?php } ?>
                            <?php } ?>
                            <?php } else { ?>
                            <h2>Random debate</h2>
                            <?php } ?>
                            <div class="parliamentary-excerpt">
                            <h3 class="excerpt__title"><a href="<?= $featured['list_url'] ?>"><?= $featured['parent']['body'] ?></a></h3>
                            <p class="meta"><?= isset($featured['htime']) && $featured['htime'] != '00:00:00' ? format_time($featured['htime'], TIMEFORMAT) : '' ?> <?= format_date($featured['hdate'], LONGERDATEFORMAT) ?></p>
                                <p class="meta excerpt__category"><a href="<?= $featured['more_url'] ?>"><?= $featured['desc'] ?></a></p>
                                <p class="excerpt__statement">
                                    <q>
                                    <?php if ($featured['child']['speaker']) { ?>
                                    <a href="<?= $featured['child']['speaker']['url'] ?>"><?= $featured['child']['speaker']['name'] ?></a> :
                                    <?php } ?>
                                    <?= trim_characters($featured['child']['body'], 0, 200) ?>
                                    </q>
                                </p>
                            </div>
                            <?php if (count($featured['related'])) { ?>
                            <div class="in-the-news__key-events">
                                <!-- No maximum, but less than 4 looks best -->
                                <ul class="key-events__list">
                                    <li>
                                        <h4>Key events</h4>
                                    </li>
                                    <?php foreach ($featured['related'] as $related) { ?>
                                    <li>
                                        <a href="<?= $related['list_url'] ?>"><?= $related['parent']['body'] ?></a>
                                        <p class="meta"><?= format_date($related['hdate'], SHORTDATEFORMAT) ?></p>
                                    </li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <?php } ?>
