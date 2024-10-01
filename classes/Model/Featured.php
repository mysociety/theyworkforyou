<?php
/**
 * Banner Model
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\Model;

class Featured {
    /**
     * DB handle
     */
    private $db;

    public function __construct() {
        $this->db = new \ParlDB();
    }

    public function get_title() {
        return $this->_get('featured_title');
    }

    public function set_title($title) {
        return $this->_set('featured_title', $title);
    }

    public function get_context() {
        return $this->_get('featured_context');
    }

    public function set_context($context) {
        return $this->_set('featured_context', $context);
    }

    public function get_gid() {
        return $this->_get('featured_gid');
    }

    public function set_gid($gid) {
        return $this->_set('featured_gid', $gid);
    }

    public function get_related() {
        $related = $this->_get('featured_related');
        return explode(',', $related);
    }

    public function set_related($related) {
        $related = implode(',', $related);
        $this->_set('featured_related', $related);
    }

    private function _get($key) {
        $text = null;

        $q = $this->db->query(
            "SELECT value FROM editorial WHERE item = :key",
            [
                ':key' => $key,
            ]
        )->first();

        if ($q) {
            $text = $q['value'];
            if (trim($text) == '') {
                $text = null;
            }
        }

        return $text;
    }

    private function _set($key, $value) {
        if (trim($value) == '') {
            $value = null;
        }
        $check_q = $this->db->query(
            "SELECT value FROM editorial WHERE item = :key",
            [
                ':key' => $key,
            ]
        );
        if ($check_q->rows()) {
            $set_q = $this->db->query(
                "UPDATE editorial set value = :value WHERE item = :key",
                [
                    ':key' => $key,
                    ':value' => $value,
                ]
            );
        } else {
            $set_q = $this->db->query(
                "INSERT INTO editorial (item, value ) VALUES (:key, :value)",
                [
                    ':key' => $key,
                    ':value' => $value,
                ]
            );
        }

        if ($set_q->success()) {
            return true;
        }
        return false;
    }
}
