<?php
/**
 * Topic
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

class Topic {

    /**
     * DB handle
     */
    private $db;

    private $raw;
    private $id;
    private $title;
    private $slug;
    private $description;

    /**
     * Constructor
     *
     */

    public function __construct($data)
    {
        $this->db = new \ParlDB;

        $this->raw = $data;
        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->slug = $data['slug'];
        $this->description = $data['description'];

    }


    function data() {
        return $this->raw;
    }

    function title() {
        return $this->title;
    }

    function set_title($title) {
        $this->title = $title;
    }

    function slug() {
        return $this->slug;
    }

    function url() {
        $url = new \URL('topic');
        return $url->generate() . $this->slug;
    }

    function image() {
        $image_name = preg_replace('/-/', '', $this->slug);
        return "/images/topic" . $image_name . ".jpg";
    }

    function description() {
        return $this->description;
    }

    function set_description($description) {
        $this->description = $description;
    }

    function getContent() {
        $q = $this->db->query(
          "SELECT body, gid FROM epobject ep JOIN hansard h on ep.epobject_id = h.epobject_id 
          WHERE ep.epobject_id in (
            SELECT epobject_id from topic_epobjects WHERE topic_key = :topic_key
          )",
            array(
                ':topic_key' => $this->id
            )
        );

        $content = array();
        $rows = $q->rows;
        for ($i = 0; $i < $rows; $i++) {
            $content[] = array(
                'title' => $q->field($i, 'body'),
                'href'  => Utility\Hansard::gid_to_url($q->field($i, 'gid')),
            );
        }

        return $content;
    }

    function addContent($gid) {
        $q = $this->db->query(
          "SELECT epobject_id FROM hansard WHERE gid = :gid",
          array(
            ":gid" => $gid
          )
        );

        if (!$q->success() || $q->rows == 0) {
          return false;
        }

        $epobject_id = $q->field(0, 'epobject_id');

        $q = $this->db->query(
          "INSERT INTO topic_epobjects (topic_key, epobject_id) VALUES (:topic, :ep_id)",
          array(
            ":topic" => $this->id,
            ":ep_id" => $epobject_id
          )
        );

        return $q->success();
    }

    function getPolicySets() {
      $q = $this->db->query(
        "SELECT policyset FROM topic_policysets WHERE topic_key = :key",
        array(
          ':key' => $this->id
        )
      );

      $sets = array();
      $count = $q->rows;
      for ($i = 0; $i < $count; $i++) {
        $sets[] = $q->field($i, 'policyset');
      }

      return $sets;
    }

    function save() {
        $q = $this->db->query(
            "REPLACE INTO topics (id, title, slug, description) VALUES(:id, :title, :slug, :description)",
            array(
                ':id' => $this->id,
                ':slug' => $this->slug(),
                ':title' => $this->title(),
                ':description' => $this->description()
            )
        );

        return $q->success();
    }
}
