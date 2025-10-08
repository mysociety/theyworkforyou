<?php

namespace MySociety\TheyWorkForYou\GlossaryView;

class BaseView {
    protected function format_body($body): string {
        $Parsedown = new \Parsedown();
        $Parsedown->setSafeMode(true);
        return $Parsedown->text($body);
    }

    protected function has_edit_access(): bool {
        global $THEUSER;

        return $THEUSER->is_able_to('addterm');
    }

    protected function add_management_urls($data): array {
        if ($this->has_edit_access()) {
            $url = new \MySociety\TheyWorkForYou\Url('glossary_addterm');
            $data['add_url'] = $url->generate('url');

            $url = new \MySociety\TheyWorkForYou\Url('admin_glossary');
            $data['admin_url'] = $url->generate('url');
        }

        return $data;
    }
}
