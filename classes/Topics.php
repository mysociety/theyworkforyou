<?php
/**
 * Topics
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

class Topics {
    /**
     * DB handle
     */
    private $db;

    /**
     * Constructor
     *
     */

    public function __construct() {
        $this->db = new \ParlDB();
    }

    private function query($where = '') {
        $q = $this->db->query(
            "SELECT id, slug, title, description, search_string, front_page, image FROM topics $where"
        );

        $topics = [];
        foreach ($q as $row) {
            $topic = $row;
            $topics[$topic['slug']] = new Topic($topic);
        }
        return $topics;
    }

    public function getTopics() {
        return $this->query();
    }

    public function getTopic($topic_name) {
        $q = $this->db->query(
            "SELECT id, slug, title, description, search_string, front_page, image FROM topics WHERE slug = :slug",
            [':slug' => $topic_name]
        )->first();
        if ($q) {
            return new Topic($q);
        }

        return null;
    }

    public function getFrontPageTopics() {
        return $this->query("WHERE front_page = TRUE");
    }

    public function updateFrontPageTopics($topics) {
        // PDO doesn't cope with arrays so we have to do this by hand :|
        $quoted = [];
        if ($topics) {
            foreach ($topics as $topic) {
                $quoted[] = $this->db->quote($topic);
            }
            $topics_str = implode(',', $quoted);

            $this->db->query("UPDATE topics SET front_page = TRUE WHERE slug IN ($topics_str)");
            $this->db->query("UPDATE topics SET front_page = FALSE WHERE slug NOT IN ($topics_str)");
        } else {
            $this->db->query("UPDATE topics SET front_page = FALSE");
        }

        return true;
    }
}
