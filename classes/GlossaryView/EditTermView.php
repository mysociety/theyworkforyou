<?php

# For adding glossary terms

namespace MySociety\TheyWorkForYou\GlossaryView;

class EditTermView extends BaseView {
    public $glossary;

    public function display(): array {
        global $THEUSER;
        if (!$this->has_edit_access()) {
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
        $data['template_name'] = 'editterm_start';
        $term = $this->glossary->current_term;
        $data['definition_raw'] = $term['body'];


        if (get_http_var('submitterm') != '') {
            $data['definition_raw'] = get_http_var('definition');
            $data = $this->update_glossary_entry($data);
            if ($data['success']) {
                $URL = new \MySociety\TheyWorkForYou\Url('glossary');
                $URL->insert(['gl' => $id]);
                $data['entry_url'] = $URL->generate();
                $data['template_name'] = 'editterm_success';
            }
        } elseif (get_http_var('previewterm') != '') {
            $data['contributing_user'] = $term['firstname'] . " " . $term['lastname'];
            $data['definition_raw'] = get_http_var('definition');
            $data['definition'] = $this->format_body($data['definition_raw']);
            $data['template_name'] = 'editterm_preview';
        }

        $URL = new \MySociety\TheyWorkForYou\Url('glossary_editterm');
        $data['form_url'] = $URL->generate();

        return $data;
    }

    protected function update_glossary_entry(array $data): array {
        $success = false;
        if (!isset($data['error'])) {
            $entry = [
                'glossary_id' => $data['glossary_id'],
                'body'  => $data['definition_raw'],
            ];
            $success = $this->glossary->update($entry);
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
