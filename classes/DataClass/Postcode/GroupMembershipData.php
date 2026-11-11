<?php

namespace MySociety\TheyWorkForYou\DataClass\Postcode;

/**
 * A group's short title and the representative's role, for a postcode card.
 */
class GroupMembershipData {
    /**
     * Short display title of the parliamentary group.
     */
    public string $title;
    /**
     * Role held in the group, such as Chair or Member.
     */
    public string $role;

    /**
     * The group title followed by the representative's role, when available.
     */
    public function displayName(): string {
        return $this->role !== '' ? "$this->title ($this->role)" : $this->title;
    }
}
