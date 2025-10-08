<?php

namespace MySociety\TheyWorkForYou\GlossaryView;

class AtoZView extends BaseView {
    public function display(): array {
        $data = [
            'template_name' => 'atoz',
            'this_page' => 'glossary',
        ];


        $az = filter_input(INPUT_GET, 'az', FILTER_VALIDATE_REGEXP, ['options' => ['default' => 'A', 'regexp' => '#^[A-Z]$#']]);

        $data['letter'] = $az;

        $data = $this->add_management_urls($data);

        $glossary = new \GLOSSARY(['sort' => 'regexp_replace']);
        $glossary->current_letter = $az;

        $data['page_title'] = $az . ': Glossary Index';
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
