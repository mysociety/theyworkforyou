<?php
/**
 * Mirrors pydantic model for deseralisation in a PHP context.
 * For adding display related helper functions.
 * @package TheyWorkForYou
 */

declare(strict_types=1);

namespace MySociety\TheyWorkForYou\DataClass\APPGs;

use MySociety\TheyWorkForYou\DataClass\BaseModel;

class APPGMembershipAssignment extends BaseModel {
    public APPGMembershipList $is_officer_of;
    public APPGMembershipList $is_ordinary_member_of;

    public function is_an_officer() {
        return $this->is_officer_of->count() > 0;
    }

    public function is_a_member() {
        return $this->is_ordinary_member_of->count() > 0;
    }
}
