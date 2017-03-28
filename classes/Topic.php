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
    private $image;
    private $search_string;
    private $front_page;

    /**
     * Constructor
     *
     */

    public function __construct($data = NULL)
    {
        $this->db = new \ParlDB;

        if (is_null($data)) {
          return;
        }

        $this->raw = $data;
        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->slug = $data['slug'];
        $this->description = $data['description'];
        $this->search_string = $data['search_string'];
        $this->front_page = $data['front_page'];
        $this->image = $data['image'];

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

    function set_slug($slug) {
        $this->slug = $slug;
    }

    function url() {
        $url = new \URL('topic');
        return $url->generate() . $this->slug;
    }

    function image() {
        return $this->image;
    }

    function image_url() {
        return "/topic/image.php?id=" . $this->slug();
    }

    function image_path() {
        if ($this->image) {
            return sprintf('%s%s%s', TOPICIMAGEPATH, DIRECTORY_SEPARATOR, $this->image);
        }

        return false;
    }

    function set_image($image) {
        $this->image = $image;
    }


    function description() {
        return $this->description;
    }

    function set_description($description) {
        $this->description = $description;
    }

    function search_string() {
        return $this->search_string;
    }

    function set_search_string($search_string) {
        $this->search_string = $search_string;
    }

    function set_front_page($on) {
        $this->front_page = $on;
    }

    function onFrontPage() {
        return $this->front_page == 1;
    }

    private function _getContentIDs() {
        $q = $this->db->query(
          "SELECT body, gid, ep.epobject_id FROM epobject ep JOIN hansard h on ep.epobject_id = h.epobject_id 
          WHERE ep.epobject_id in (
            SELECT epobject_id from topic_epobjects WHERE topic_key = :topic_key
          )",
            array(
                ':topic_key' => $this->id
            )
        );

        return $q;
    }

    function getContent() {
        $q = $this->_getContentIDs();

        $content = array();
        $rows = $q->rows;
        for ($i = 0; $i < $rows; $i++) {
            $content[] = array(
                'title' => $q->field($i, 'body'),
                'href'  => Utility\Hansard::gid_to_url($q->field($i, 'gid')),
                'id'    => $q->field($i, 'epobject_id'),
            );
        }

        return $content;
    }

    function getFullContent() {
        $q = $this->_getContentIDs();

        $content = array();
        $rows = $q->rows;
        for ($i = 0; $i < $rows; $i++) {
            $gid = $q->field($i, 'gid');
            if (strpos($gid, 'lords') !== false) {
                $debatelist = new \LORDSDEBATELIST;
            } elseif (strpos($gid, 'westminhall') !== false) {
                $debatelist = new \WHALLLIST;
            } else {
                $debatelist = new \DEBATELIST;
            }
            $data = $debatelist->display('featured_gid', array('gid' => $gid), 'none');

            $item = $data['data'];
            if (isset($item['parent']) && $item['body'] == $item['parent']['body']) {
                unset($item['parent']);
            }
            $content[] = $item;
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

    function deleteContent($id) {
        $q = $this->db->query(
          "DELETE FROM topic_epobjects WHERE topic_key = :topic AND epobject_id = :ep_id",
          array(
            ":topic" => $this->id,
            ":ep_id" => $id
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

    function addPolicySets($sets) {
        if ($sets === '' or count($sets) == 0) {
            $q = $this->db->query(
                "DELETE FROM topic_policiessets WHERE topic_key = :topic_key",
                array(
                    ":topic_key" => $this->id
                )
            );
        } else {
            foreach ($sets as $set) {
                $q = $this->db->query(
                    "REPLACE INTO topic_policysets (policyset, topic_key) VALUES (:policyset, :topic_key)",
                    array(
                        ':topic_key' => $this->id,
                        ':policyset' => $set
                    )
                );
            }
        }

        return $q->success();
    }

    function getPolicies() {
      $q = $this->db->query(
        'SELECT policy_id FROM topic_policies WHERE topic_key = :key',
        array(
          ':key' => $this->id
        )
      );

      $policies = array();
      $count = $q->rows;
      for ($i = 0; $i < $count; $i++) {
        $policies[] = $q->field($i, 'policy_id');
      }

      return $policies;
    }

    function addPolicies($policies) {
        if ($policies === '' or count($policies) == 0) {
            $q = $this->db->query(
                "DELETE FROM topic_policies WHERE topic_key = :topic_key",
                array(
                    ":topic_key" => $this->id
                )
            );
        } else {
            foreach ($policies as $policy) {
                $q = $this->db->query(
                    "REPLACE INTO topic_policies (policy_id, topic_key) VALUES (:policy, :topic_key)",
                    array(
                        ':topic_key' => $this->id,
                        ':policy' => $policy
                    )
                );
            }
        }

        return $q->success();
    }

    function getAllPolicies() {
        $policy_sets = $this->getPolicySets();
        $all_policies = array();
        $policies = new Policies();
        foreach ($policy_sets as $set) {
            $all_policies = array_merge($all_policies, array_keys($policies->limitToSet($set)->getPolicies()));
        }
        $topic_policies = $this->getPolicies();
        $all_policies = array_merge($all_policies, $topic_policies);

        return array_unique($all_policies);
    }

    function save() {
        $q = $this->db->query(
          "REPLACE INTO topics
          (id, title, slug, description, search_string, front_page, image)
          VALUES
          (:id, :title, :slug, :description, :search_string, :front_page, :image)",
            array(
                ':id' => $this->id,
                ':slug' => $this->slug(),
                ':title' => $this->title(),
                ':description' => $this->description(),
                ':search_string' => $this->search_string(),
                ':front_page' => $this->onFrontPage(),
                ':image' => $this->image()
            )
        );

        return $q->success();
    }
}
