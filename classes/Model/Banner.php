<?php
/**
 * Banner Model
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\Model;

class Banner {

    /**
     * DB handle
     */
    private $db;

    /*
     * Memcache handle
     */
    private $mem;

    /**
     * Constructor
     *
     * @param Member   $member   The member to get positions for.
     */

    public function __construct()
    {
        $this->db = new \ParlDB;
        $this->mem = new \MySociety\TheyWorkForYou\Memcache();
    }

    public function get_text() {
        $text = NULL;
        $text = $this->mem->get('banner');

        if ( $text === false ) {
            $q = $this->db->query("SELECT value FROM editorial WHERE item = 'banner'");

            if ($q->rows) {
                $text = $q->field(0, 'value');
                if ( trim($text) == '' ) {
                    $text = NULL;
                }
                $this->mem->set('banner', $text, 86400);
            }
        }

        return $text;
    }

    public function set_text($text) {
        $q = $this->db->query("UPDATE editorial set value = :banner_text WHERE item = 'banner'",
            array(
                ':banner_text' => $text
            )
        );

        if ( $q->success() ) {
            if ( trim($text) == '' ) {
                $text = NULL;
            }
            $this->mem->set('banner', $text, 86400);
            return true;
        }
        return false;
    }
}
