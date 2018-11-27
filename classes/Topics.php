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
      $q = $this->db->query("SELECT id, slug, title, description, search_string, front_page, image FROM topics");

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
          "SELECT id, slug, title, description, search_string, front_page, image FROM topics WHERE slug = :slug",
          array(':slug' => $topic_name)
      );
      if ($q->rows) {
          return new Topic($q->row(0));
      }

      return NULL;
    }

    public function getFrontPageTopics() {
      $q = $this->db->query(
          "SELECT id, slug, title, description, search_string, front_page, image FROM topics WHERE front_page = TRUE"
      );

      $topics = array();
      $count = $q->rows();

      for ($i = 0; $i < $count; $i++ ) {
          $topic = $q->row($i);
          $topics[$topic['slug']] = new Topic($topic);
      }
      return $topics;
    }

    public function updateFrontPageTopics($topics) {
        // PDO doesn't cope with arrays so we have to do this by hand :|
        $quoted = array();
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
