<?php

namespace MySociety\TheyWorkForYou;

class AlertView {
    public const ALERT_EXISTS = -2;
    public const CREATE_FAILED = -1;

    protected $user;
    protected $db;
    protected $alert;

    public function __construct($THEUSER = null) {
        $this->user = $THEUSER;
        $this->db = new \ParlDB();
        $this->alert = new \ALERT();
    }

    protected function confirmAlert($token) {
        return $this->alert->confirm($token);
    }

    protected function suspendAlert($token) {
        return $this->alert->suspend($token);
    }

    protected function resumeAlert($token) {
        return $this->alert->resume($token);
    }

    protected function ignoreVotesAlert($token) {
        return $this->alert->ignoreVotes($token);
    }

    protected function includeVotesAlert($token) {
        return $this->alert->includeVotes($token);
    }

    protected function deleteAlert($token) {
        return $this->alert->delete($token);
    }

    protected function deleteAllAlerts($token) {
        return $this->alert->delete_all($token);
    }

}
