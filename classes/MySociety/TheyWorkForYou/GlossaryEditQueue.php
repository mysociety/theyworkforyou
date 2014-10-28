<?php
/**
 * GlossaryEditQueue Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Glossary Edit Queue
 */

class GlossaryEditQueue extends EditQueue {

    public function approve($data) {
    // Approve items for inclusion
    // Create new epobject and update the editqueue

        global $THEUSER;

        // We need a list of editqueue items to play with
        $this->get_pending();
        if (!isset($this->pending)) {
            return false;
        }
        $timestamp = date('Y-m-d H:i:s', time());

        foreach ($data['approvals'] as $approval_id) {
            // create a new epobject
            //      title VARCHAR(255),
            //      body TEXT,
            //      type INTEGER,
            //      created DATETIME,
            //      modified DATETIME,
            /*print "<pre>";
            print_r($data);
            print "</pre>";*/
            // Check to see that we actually have something to approve
            if (!isset($this->pending[$approval_id])) {
                break;
            }
            $q = $this->db->query("INSERT INTO glossary
                            (title, body, type, created, visible)
                            VALUES
                            ('" . addslashes($this->pending[$approval_id]['title']) . "',
                            '" . addslashes($this->pending[$approval_id]['body']) . "',
                            '" . $data['epobject_type'] . "',
                            '" . $timestamp . "',
                            1);");

            // If that didn't work we can't go any further...
            if (!$q->success()) {
                print "glossary trouble";
                return false;
            }
            $this->current_epobject_id = $q->insert_id();

            // Then finally update the editqueue with
            // the new epobject id and approval details.
            $q = $this->db->query("UPDATE editqueue
                            SET
                            glossary_id='" .  $this->current_epobject_id. "',
                            editor_id='" . addslashes($THEUSER->user_id()) . "',
                            approved='1',
                            decided='" . $timestamp . "'
                            WHERE edit_id=" . $approval_id . ";");
            if (!$q->success()) {
                break;
            }
            else {
                // Scrub that one from the list of pending items
                unset ($this->pending[$approval_id]);
            }
        }

        $this->update_pending_count();

        return true;
    }

}
