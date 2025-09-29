<?php

# For adding glossary terms

namespace MySociety\TheyWorkForYou\GlossaryView;

class AddTermView {
    public $glossary;

    public function display(): array {
        global $THEUSER;
        if (!$this->has_access()) {
            return ['error' => _("You don't have permission to manage the glossary")];
        }

        $data = [];
        $term = filter_user_input(get_http_var('g'), 'strict');
        // glossary matches should always be quoted.
        // Just to be sure, we'll strip away any quotes and add them again.
        if (preg_match("/^(\"|\')/", $term)) {
            $term = preg_replace("/\"|\'/", "", $term);
        }
        $data['term'] = $term;
        $data['title'] = $term;

        $this->glossary = new \GLOSSARY(['s' => $term]);
        $data['glossary'] = $this->glossary;

        if (get_http_var('submitterm') != '') {
            $data = $this->add_glossary_entry($data);
        } elseif ((get_http_var('g') != '') && (get_http_var('previewterm') == '')) {
            $data = $this->check_glossary_entry($data);
        } elseif (get_http_var('previewterm') != '') {
            $data['definition'] = get_http_var('definition');
            $data['contributing_user'] = $THEUSER->firstname . " " . $THEUSER->lastname;
            $data['preview'] = 1;
        } else {
            $data = $this->add_example_urls($data);
        }

        $URL = new \MySociety\TheyWorkForYou\Url('glossary_addterm');
        $data['form_url'] = $URL->generate();

        return $data;
    }

    protected function has_access(): bool {
        global $THEUSER;

        if (!$THEUSER->is_able_to('addterm')) {
            return false;
        }

        return true;
    }

    protected function has_stop_words(): bool {
        if (in_array($this->glossary->query, $this->glossary->stopwords)) {
            return true;
        }
        return false;
    }

    protected function check_term_is_useful(array $data): array {
        if ($this->has_stop_words()) {
            $data['error'] = 'Sorry, that phrase appears too many times to be a useful as a link within the parliamentary record.';
        } elseif (isset($data['appearances']) && !$data['appearances']) {
            $data['error'] = "Unfortunately <strong>" . $data['term'] . "</strong>, doesn't seem to appear in hansard at all...</p>";
        } elseif ($this->glossary->num_search_matches > 0) {
            $data['show_matches'] = 1;
            $data['error'] = 'Existing matches';
            $data['count'] = $this->glossary->num_search_matches;
        }

        return $data;
    }

    protected function get_appearance_count(string $term): int {
        global $SEARCHENGINE;
        $SEARCHENGINE = new \SEARCHENGINE($term);
        $count = $SEARCHENGINE->run_count(0, 10000);
        return $count;
    }

    protected function check_glossary_entry(array $data): array {
        $data['appearances'] = $this->get_appearance_count($data['term']);
        $data['definition'] = '';
        $data = $this->check_term_is_useful($data);

        if (!isset($data['error'])) {
            $data['definition'] = '';
            $list = new \HANSARDLIST();
            $examples = $list->display('search', [
                'num' => 5,
                's' => $data['term'],
                'view_override' => 'glossary_search',
            ], 'none');
            $data['examples'] = $examples['rows'];
        }

        return $data;
    }

    protected function add_glossary_entry(array $data): array {
        $data['submitted'] = 1;
        $data['body'] = get_http_var('definition');
        $data = $this->check_term_is_useful($data);

        $success = false;
        if (!isset($data['error'])) {
            $entry = [
                'title' => $data['term'],
                'body'  => $data['body'],
            ];
            $success = $this->glossary->create($entry);
        }

        if (is_int($success)) {
            $data['success'] = 1;
        } elseif (is_array($success)) {
            $data = array_merge($data, $success);
        } else {
            if (!isset($data['error'])) {
                $data['error'] = "Sorry, there was an error and we were unable to add your Glossary item.";
            }
        }

        return $data;
    }

    protected function add_example_urls(array $data): array {
        $URL = new \MySociety\TheyWorkForYou\Url('glossary');

        $examples = [
            'technical' => [
                'name' => 'Early Day Motion', 'id' => 90,
            ],
            'organisation' => [
                'name' => 'Devon County Council', 'id' => 12,
            ],
            'document' => [
                'name' => 'Hutton Report', 'id' => 7,
            ],
        ];

        $example_urls = [];
        foreach ($examples as $name => $example) {
            $URL->insert(["gl" => $example['id']]);
            $example['url'] = $URL->generate();
            $example_urls[$name] = $example;
        }

        $data['example_urls'] = $example_urls;
        return $data;
    }

}
