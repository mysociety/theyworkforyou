                        <?php if ($featured['featured']) { ?>
                        <h2>Featured news</h2>
                        <div class="in-the-news__wrapper">
                            <?php if (isset($featured['headline'])) { ?>
                                {% comment %} TODO include image link and Alt text {% endcomment %}
                                <img class="in-the-news__image" src="https://www.theyworkforyou.com/style/img/homepage-hero-background-large.jpg" alt="I'm a placeholder">
                                <div>
                                    <h3 class="in-the-news__topic"><?= $featured['headline'] ?></h3>
                                    <?php if (isset($featured['context'])) { ?>
                                    <p class="in-the-news__context"><?= $featured['context'] ?></p>
                                    <?php } ?>
                                    <?php } ?>
                                    <?php } else { ?>
                                    <h2>Random debate</h2>
                                    <?php } ?>
                                    <div class="parliamentary-excerpt">
                                    <h3 class="excerpt__title"><?= $featured['parent']['body'] ?></h3>
                                    <p class="meta"><?= isset($featured['htime']) && $featured['htime'] != '00:00:00' ? format_time($featured['htime'], TIMEFORMAT) : '' ?> <?= format_date($featured['hdate'], LONGERDATEFORMAT) ?></p>
                                    <a class="button" href="<?= $featured['list_url'] ?>">Learn more</a>
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
                                </div>
                        </div>
