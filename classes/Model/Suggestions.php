<?php
/**
 * Search Suggestions Model
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\Model;

class Suggestions {

    /**
     * DB handle
     */
    private $db;

    public function __construct()
    {
        $this->db = new \ParlDB;
    }

    public function get_suggestion($term) {
        $key = 'search_suggestion_' . $term;
        $q = $this->db->query("SELECT value FROM editorial WHERE item = :key",
            array(
                ':key' => $key
            )
        );

        $url = null;
        if ($q->rows) {
            $url = $q->field(0, 'value');
            if ( trim($url) == '' ) {
                $url = NULL;
            }
        }
        return $url;
    }

    public function set_suggestion($term, $url) {
        $key = 'search_suggestion_' . $term;
        $q = $this->db->query("REPLACE INTO editorial set item = :key, value = :url",
            array(
                ':key' => $key,
                ':url' => $url
            )
        );

        if ( $q->success() ) {
            return true;
        }
        return false;
    }
}
