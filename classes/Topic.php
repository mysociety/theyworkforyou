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

    public function __construct($data = null) {
        $this->db = new \ParlDB();

        if (is_null($data)) {
            return;
        }

        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->slug = $data['slug'];
        $this->description = $data['description'];
        $this->search_string = $data['search_string'];
        $this->front_page = $data['front_page'];
        $this->image = $data['image'];

    }


    public function title() {
        return $this->title;
    }

    public function sctitle() {
        $title = $this->title;
        if (strpos($title, 'The ') === 0) {
            $title = lcfirst($title);
        }

        return $title;
    }

    public function set_title($title) {
        $this->title = $title;
    }

    public function slug() {
        return $this->slug;
    }

    public function set_slug($slug) {
        $this->slug = $slug;
    }

    public function url() {
        $url = new Url('topic');
        return $url->generate() . $this->slug;
    }

    public function image() {
        return $this->image;
    }

    public function image_url() {
        return "/topic/image.php?id=" . $this->slug();
    }

    public function image_path() {
        if ($this->image) {
            return sprintf('%s%s%s', TOPICIMAGEPATH, DIRECTORY_SEPARATOR, $this->image);
        }

        return false;
    }

    public function set_image($image) {
        $this->image = $image;
    }


    public function description() {
        return $this->description;
    }

    public function set_description($description) {
        $this->description = $description;
    }

    public function search_string() {
        return $this->search_string;
    }

    public function set_search_string($search_string) {
        $this->search_string = $search_string;
    }

    public function set_front_page($on) {
        $this->front_page = $on;
    }

    public function onFrontPage() {
        return $this->front_page == 1;
    }

    private function _getContentIDs() {
        $q = $this->db->query(
            "SELECT body, gid, ep.epobject_id FROM epobject ep
           JOIN hansard h on ep.epobject_id = h.epobject_id
           JOIN topic_epobjects te on te.epobject_id = ep.epobject_id
           WHERE topic_key = :topic_key",
            [
                ':topic_key' => $this->id,
            ]
        );

        return $q;
    }

    public function getContent() {
        $q = $this->_getContentIDs();

        $content = [];
        foreach ($q as $row) {
            $content[] = [
                'title' => $row['body'],
                'href'  => Utility\Hansard::gid_to_url($row['gid']),
                'id'    => $row['epobject_id'],
            ];
        }

        return $content;
    }

    public function getFullContent() {
        $q = $this->_getContentIDs();

        $content = [];
        foreach ($q as $row) {
            $gid = $row['gid'];
            if (strpos($gid, 'lords') !== false) {
                $debatelist = new \LORDSDEBATELIST();
            } elseif (strpos($gid, 'westminhall') !== false) {
                $debatelist = new \WHALLLIST();
            } else {
                $debatelist = new \DEBATELIST();
            }
            $data = $debatelist->display('featured_gid', ['gid' => $gid], 'none');

            $item = $data['data'];
            if (isset($item['parent']) && $item['body'] == $item['parent']['body']) {
                unset($item['parent']);
            }
            $content[] = $item;
        }

        return $content;
    }

    public function addContent($gid) {
        $q = $this->db->query(
            "SELECT epobject_id FROM hansard WHERE gid = :gid",
            [
                ":gid" => $gid,
            ]
        )->first();

        if (!$q) {
            return false;
        }

        $epobject_id = $q['epobject_id'];

        $q = $this->db->query(
            "INSERT INTO topic_epobjects (topic_key, epobject_id) VALUES (:topic, :ep_id)",
            [
                ":topic" => $this->id,
                ":ep_id" => $epobject_id,
            ]
        );

        return $q->success();
    }

    public function deleteContent($id) {
        $q = $this->db->query(
            "DELETE FROM topic_epobjects WHERE topic_key = :topic AND epobject_id = :ep_id",
            [
                ":topic" => $this->id,
                ":ep_id" => $id,
            ]
        );

        return $q->success();
    }

    public function getPolicySets() {
        $q = $this->db->query(
            "SELECT policyset FROM topic_policysets WHERE topic_key = :key",
            [
                ':key' => $this->id,
            ]
        );

        $sets = [];
        foreach ($q as $row) {
            $sets[] = $row['policyset'];
        }

        return $sets;
    }

    public function addPolicySets($sets) {
        if ($sets === '' or count($sets) == 0) {
            $q = $this->db->query(
                "DELETE FROM topic_policysets WHERE topic_key = :topic_key",
                [
                    ":topic_key" => $this->id,
                ]
            );
        } else {
            foreach ($sets as $set) {
                if ($set == '') {
                    continue;
                }
                $q = $this->db->query(
                    "REPLACE INTO topic_policysets (policyset, topic_key) VALUES (:policyset, :topic_key)",
                    [
                        ':topic_key' => $this->id,
                        ':policyset' => $set,
                    ]
                );
            }
        }

        return $q->success();
    }

    public function getPolicies() {
        $q = $this->db->query(
            'SELECT policy_id FROM topic_policies WHERE topic_key = :key',
            [
                ':key' => $this->id,
            ]
        );

        $policies = [];
        foreach ($q as $row) {
            $policies[] = $row['policy_id'];
        }

        return $policies;
    }

    public function addPolicies($policies) {
        if ($policies === '' or count($policies) == 0) {
            $q = $this->db->query(
                "DELETE FROM topic_policies WHERE topic_key = :topic_key",
                [
                    ":topic_key" => $this->id,
                ]
            );
        } else {
            foreach ($policies as $policy) {
                if ($policy == '') {
                    continue;
                }
                $q = $this->db->query(
                    "REPLACE INTO topic_policies (policy_id, topic_key) VALUES (:policy, :topic_key)",
                    [
                        ':topic_key' => $this->id,
                        ':policy' => $policy,
                    ]
                );
            }
        }

        return $q->success();
    }

    public function getAllPolicies() {
        $policy_sets = $this->getPolicySets();
        $all_policies = [];
        $policies = new Policies();
        foreach ($policy_sets as $set) {
            $all_policies = array_merge($all_policies, array_keys($policies->limitToSet($set)->getPolicies()));
        }
        $topic_policies = $this->getPolicies();
        $all_policies = array_merge($all_policies, $topic_policies);

        return array_unique($all_policies);
    }

    public function save() {
        $q = $this->db->query(
            "REPLACE INTO topics
          (id, title, slug, description, search_string, front_page, image)
          VALUES
          (:id, :title, :slug, :description, :search_string, :front_page, :image)",
            [
                ':id' => $this->id,
                ':slug' => $this->slug(),
                ':title' => $this->title(),
                ':description' => $this->description(),
                ':search_string' => $this->search_string(),
                ':front_page' => $this->onFrontPage(),
                ':image' => $this->image(),
            ]
        );

        return $q->success();
    }
}
