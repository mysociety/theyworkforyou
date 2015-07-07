    <?php if ( isset($mini_survey) ) {
        if ( $mini_survey['show'] ) { ?>
                <h2>Did you find what you were looking for?</h2>
                <form method="post" action="http://survey.mysociety.org">
                    <input type="hidden" name="sourceidentifier" value="twfy-mini-2">
                    <input type="hidden" name="datetime" value="<?= $mini_survey['datetime'] ?>">
                    <input type="hidden" name="subgroup" value="0">
                    <input type="hidden" name="user_code" value="<?= $mini_survey['user_code'] ?>">
                    <input type="hidden" name="auth_signature" value="<?= $mini_survey['auth_signature'] ?>">
                    <input type="hidden" name="came_from" value="<?= $mini_survey['page_url'] ?>">
                    <input type="hidden" name="return_url" value="<?= $mini_survey['page_url'] ?>">
                    <input type="hidden" name="question_no" value="<?= $mini_survey['current_q'] ?>">
                    <p>
                        <label><input type="radio" name="find_on_page" value="1"> Yes</label>
                        <label><input type="radio" name="find_on_page" value="0"> No</label>
                    </p>
                    <p>
                        <input type="submit" class="button small" value="Submit answer">
                    </p>
                </form>
        <?php } else if ( $mini_survey['answered'] ) { ?>
            <p>Thanks for answering</p>
        <?php } ?>
    <?php } ?>
