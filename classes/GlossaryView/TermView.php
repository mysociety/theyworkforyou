<?php

namespace MySociety\TheyWorkForYou\GlossaryView;

class TermView extends BaseView {
    public function display(): array {
        $data = [
            'title' => 'Glossary',
            'page_title' => 'Glossary Index',
            'this_page' => 'glossary',
            'template_name' => 'term',
        ];

        $gl = filter_input(INPUT_GET, 'gl', FILTER_VALIDATE_INT);

        $az = 'A';
        $glossary = new \GLOSSARY(['sort' => 'regexp_replace', 'glossary_id' => $gl]);
        if ($glossary->current_term) {
            $data['notitle'] = 1;
            $data['title'] = $glossary->current_term['title'];
            $data['definition'] = $this->format_body($glossary->current_term['body']);

            $data['contributing_user'] = $glossary->current_term['user_id'] ? $glossary->current_term['firstname'] . " " . $glossary->current_term['lastname'] : '';
            $az = strtoupper($glossary->current_term['title'][0]);

            $data['nextprev'] = $this->get_next_prev($glossary);
            $data['this_page'] = 'glossary_item';
            $data['page_title'] = $data['title'] . ': Glossary Item';

            if ($this->has_edit_access()) {
                $url = new \MySociety\TheyWorkForYou\Url('glossary_editterm');
                $url->insert(['id' => $glossary->glossary_id]);
                $data['edit_url'] = $url->generate('url');
            }
        }

        if ($this->has_edit_access()) {
            $url = new \MySociety\TheyWorkForYou\Url('glossary_addterm');
            $data['add_url'] = $url->generate('url');
        }

        $glossary->current_letter = $az;

        $data['glossary'] = $glossary;
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
