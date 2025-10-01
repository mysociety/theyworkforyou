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

        if (!$THEUSER->is_able_to('addterm')) {
            return false;
        }

        return true;
    }
}
