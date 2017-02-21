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

    public function __construct()
    {
        $this->db = new \ParlDB;
    }

    public function getTopics() {
      $q = $this->db->query("SELECT id, slug, title, description FROM topics");

      $topics = array();
      $count = $q->rows();

      for ($i = 0; $i < $count; $i++ ) {
          $topic = $q->row($i);
          $topics[$topic['slug']] = new Topic($topic);
      }
      return $topics;
    }

    public function getTopic($topic_name) {
      $q = $this->db->query(
          "SELECT id, slug, title, description FROM topics WHERE slug = :slug",
          array(':slug' => $topic_name)
      );
      if ($q->rows) {
          return new Topic($q->row(0));
      }

      return NULL;
    }
}
