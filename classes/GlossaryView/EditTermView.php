<?php

# For adding glossary terms

namespace MySociety\TheyWorkForYou\GlossaryView;

class EditTermView {
    public $glossary;

    public function display(): array {
        global $THEUSER;
        if (!$this->has_access()) {
            return ['error' => _("You don't have permission to manage the glossary")];
        }

        $data = [];
        $id = filter_user_input(get_http_var('id'), 'strict');

        $this->glossary = new \GLOSSARY(['glossary_id' => $id]);
        if (!$this->glossary->current_term) {
            $data["error"] = "No such term";
            $data['no_term'] = 1;
            return $data;
        }
        $data['glossary_id'] = $id;
        $data['glossary'] = $this->glossary;
        $data['title'] = $this->glossary->current_term['title'];
        $term = $this->glossary->current_term;


        if (get_http_var('submitterm') != '') {
            $data['definition'] = get_http_var('definition');
            $data['definition_raw'] = get_http_var('definition');
            $data = $this->update_glossary_entry($data);
        } elseif (get_http_var('previewterm') != '') {
            $data['contributing_user'] = $term['firstname'] . " " . $term['lastname'];
            $data['definition_raw'] = get_http_var('definition');
            $Parsedown = new \Parsedown();
            $Parsedown->setSafeMode(true);
            $data['definition'] = $Parsedown->text($data['definition_raw']);
            $data['preview'] = 1;
        } else {
            $data['definition_raw'] = $term['body'];
        }

        $URL = new \MySociety\TheyWorkForYou\Url('glossary_editterm');
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

    protected function update_glossary_entry(array $data): array {
        $data['submitted'] = 1;
        $data['body'] = get_http_var('definition');

        $success = false;
        if (!isset($data['error'])) {
            $entry = [
                'glossary_id' => $data['glossary_id'],
                'body'  => $data['definition_raw'],
            ];
            $success = $this->glossary->update($data);
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

}
