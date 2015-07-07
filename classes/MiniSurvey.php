<?php

namespace MySociety\TheyWorkForYou;

require_once INCLUDESPATH . "../../commonlib/phplib/random.php";
require_once INCLUDESPATH . "../../commonlib/phplib/auth.php";

class MiniSurvey {

    public function get_values() {
        global $this_page;
        $data = array();

        // TODO: think about not hard coding these
        $current_question = 3;
        $always_ask = 1;
        $data['survey_site'] = "twfy-mini-$current_question";
        $show_survey_qn = 0;
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $has_answered_question = get_http_var('answered_survey');
        $hide_question = get_http_var('hide_survey');

        $data['show'] = false;

        if ($hide_question) {
            $always_ask = 0;
            $show_survey_qn = $current_question;
            setcookie('survey', $current_question, time()+60*60*24*365, '/');
        } elseif ($has_answered_question == $current_question && !$always_ask) {
            $show_survey_qn = $current_question;
            setcookie('survey', $current_question, time()+60*60*24*365, '/');
        } elseif (isset($_COOKIE['survey'])) {
            $show_survey_qn = $_COOKIE['survey'];
        }

        if ($show_survey_qn < $current_question && !$has_answered_question) {
            $data['show'] = true;

            $page_url = '';
            $hide_url = '';
            if ( in_array( $this_page, array('mp', 'peer', 'msp', 'mla', 'royal') ) ) {
                global $MEMBER;
                if ( $MEMBER ) {
                    $page_url = $MEMBER->url() . "?answered_survey=$current_question";
                    $hide_url = $MEMBER->url() . "?hide_survey=$current_question";
                }
            } else {
                $URL = new \URL($this_page);
                $URL->insert(array('answered_survey' => $current_question ));
                $page_url = 'http://' . DOMAIN . $URL->generate();
                $URL = new \URL($this_page);
                $URL->insert(array('hide_survey' => $current_question ));
                $hide_url = 'http://' . DOMAIN . $URL->generate();
            }

            $data['page_url'] = $page_url;
            $data['hide_url'] = $hide_url;
            $data['user_code'] = bin2hex(urandom_bytes(16));
            $data['auth_signature'] = auth_sign_with_shared_secret($data['user_code'], OPTION_SURVEY_SECRET);
            $data['datetime'] = time();
        }

        $data['current_q'] = $current_question;
        $data['answered'] = $has_answered_question;

        return $data;
    }

}
