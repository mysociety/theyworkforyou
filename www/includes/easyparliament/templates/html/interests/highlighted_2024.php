
<div class="full-page static-page toc-page">
    <div class="full-page__row">
        <div class="toc-page__col">
            <div class="toc js-toc">
            <ul>
                <li><a href="#intro">Introduction</a></li>
                <?php foreach ($category_ids as $category_id => $category_name) { ?>
                    <li><a href="#category-<?= $category_id ?>"><?= $category_name ?></a></li>
                <?php }; ?>
            </ul>
        </div>

        </div>
        <div class="toc-page__col">

            <div class="panel">
                <h1>🔍Highlighted interests</h1>

                <a name="intro"></a>
                <p>As part of our 2024/25 WhoFundsThem project, we are highlighting declared interests based on known negative public attitudes towards certain industries.</p>
                <p>You can read more about our research and choices in the accompanying <a href="http://research.mysociety.org/html/beyond-transparency/">Beyond Transparency report</a> </p>

                <p style="font-weight:bold">Nothing in this section was against the rules for MPs to accept and they have been correctly declared.</p>

                <p>MPs have been given a chance to add comments, to add context around the donations.</p>

                <p>Our volunteers have been highlighting industries and individuals that have links to:</p>
                <ul>
                    <li>A country’s government that is scored 'Not Free' by Freedom House</li>
                    <li>Oil and gas industry</li>
                    <li>Gambling industry</li>
                </ul>
                <p>This is not an exhaustive list of industries, and we have excluded rather than included borderline cases. </p>
                <hr>
                <p>If this was useful to you, you've noticed a problem, or have other suggestions, please fill out <a href="https://survey.alchemer.com/s3/8216436/WhoFundsThem-feedback">our feedback survey</a>.<p>

                <hr>
                <?php /** @var MySociety\TheyWorkForYou\DataClass\Regmem\Register $register */ ?>

                <?php foreach ($category_ids as $category_id => $category_name) { ?>
                    <h2 id="category-<?= $category_id ?>"><?= $category_name ?></h2>
                    <table>
                                <tr>
                                    <th>MP</th>
                                    <th>Our summary</th>
                                    <th>MP's comment</th>
                                </tr>
                    <?php foreach ($register->persons as $person) { ?>
                        <?php foreach($person->categories as $category) { ?>
                            <?php if ($category->category_id != $category_id) { ?>
                                <?php continue; ?>
                            <?php }; ?>

                                <?php foreach ($category->entries as $entry) { ?>
                                    <tr>
                                        <?php $person_id_parts = explode('/', (string) $person->person_id); ?>
                                        <?php $person_id = end($person_id_parts); ?>

                                        
                                        <td class="highlighted-data-cell"><a href="/mp/<?= $person_id ?>"><?= $person->person_name ?></td>
                                        <td class="highlighted-data-cell">
                                            <p class="highlighted-summary"><?= $entry->content ?></p>
                                            <p>Category: <?= $entry->get_detail("industry")->value ?></p>
                                        <p><?= $entry->get_detail("mysoc_summary")->value ?></p> <details>
                                        <summary>More details</summary>
                                        <br>
                                        <?php include('_register_entry.php'); ?>
                                        </details></td>
                                        <td class="highlighted-data-cell">
                                            <?php $their_response = $entry->get_detail("mp_comment"); ?> 
                                            <?php if ($their_response && strlen($their_response->value) > 0) { ?>
                                                <?= $their_response->value ?>
                                            <?php } else { ?>
                                                MP has not provided a comment.
                                            <?php }; ?>
                                        </td>
                                    </tr>
                                <?php }; ?>
                            

                        <?php }; ?>
                    <?php }; ?>
                    </table>
                <?php }; ?>



            </div>
        </div>  

    </div>
</div>
