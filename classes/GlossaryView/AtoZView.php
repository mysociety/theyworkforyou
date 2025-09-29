<?php

namespace MySociety\TheyWorkForYou\GlossaryView;

class AtoZView {
    public function display(): array {
        $data = [];


        $az = 'A';
        if ((get_http_var('az') != '') && is_string(get_http_var('az'))) {
            $az = strtoupper(substr(get_http_var('az'), 0, 1));
        }

        $gl = '';
        if (get_http_var('gl') and is_numeric(get_http_var('gl'))) {
            $gl = filter_user_input(get_http_var('gl'), 'strict');
        }

        $glossary = new \GLOSSARY(['sort' => 'regexp_replace', 'glossary_id' => $gl]);
        if ($glossary->current_term) {
            $data['notitle'] = 1;
            $data['title'] = $glossary->current_term['title'];

            $Parsedown = new \Parsedown();
            $Parsedown->setSafeMode(true);
            $text = $Parsedown->text($glossary->current_term['body']);
            $data['definition'] = $text;

            $data['contributing_user'] = $glossary->current_term['user_id'] ? $glossary->current_term['firstname'] . " " . $glossary->current_term['lastname'] : '';
            $az = strtoupper($glossary->current_term['title'][0]);

            $data['nextprev'] = $this->get_next_prev($glossary);
        } else {
            $data['letter'] = $az;

        }

        $glossary->current_letter = $az;

        $data['glossary'] = $glossary;
        $data['term'] = $glossary->current_term;
        return $data;
    }

    private function get_next_prev($glossary): array {
        $url = new \MySociety\TheyWorkForYou\Url('glossary');
        $url->insert(['gl' => $glossary->previous_term['glossary_id']]);
        $previous_link = $url->generate('url');
        $url->insert(['gl' => $glossary->next_term['glossary_id']]);
        $next_link = $url->generate('url');

        $nextprev =  [
            'next'	=>  [
                'url'	=> $next_link,
                'title'	=> 'Next term',
                'body'	=> $glossary->next_term['title'],
            ],
            'prev'	=>  [
                'url'	=> $previous_link,
                'title'	=> 'Previous term',
                'body'	=> $glossary->previous_term['title'],
            ],
        ];

        return $nextprev;
    }
}
