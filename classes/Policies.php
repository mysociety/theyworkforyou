<?php
/**
 * Policies Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Policies
 *
 * Class to provide management (selection, sorting etc) of PublicWhip policies.
 */

class Policies {
    private $commons_only = [
        811,
        826,
        1053,
    ];

    # policies where votes affected by covid voting restrictions
    protected $covid_affected = [1136, 6860];

    private $db;

    private $policy_id;
    /**
     * @var array $policies key-value of policy id to the context description.
     * e.g. "363": "introducing <b>foundation hospitals</b>"
     */
    private array $policies;

    /**
     * @var array $sets - key-value pair of a set slug to an array of policy IDs
     * in that set.
     * e.g. "foreignpolicy": ["975", "984"]
     */
    private array $sets;

    /**
     * @var array $set_descs - key-value pairs of a set slug to a description
     * of that set.
     * e.g. "foreignpolicy": "Foreign Policy"
     */
    private array $set_descs;

    /**
     * @var array $all_policy_agreements - key-value pair of a policy ID to
     * an array of
     * agreements links.
     * e.g. "1030": [{
     *   "division_name": "Approval of SI setting 2050 Net Zero target date",
     *   "alignment": "agree",
     *   "gid": "2019-06-24b.530.1",
     *   "url": "/debates/?id=2019-06-24b.530.1",
     *   "house": "commons",
     *   "strength": "strong",
     *   "date": "2019-06-24"
     * }]
     */
    public array $all_policy_agreements;

    public function __construct($policy_id = null) {
        $this->db = new \ParlDB();

        if (defined('TESTING') && TESTING == true) {
            $policy_data = json_decode(file_get_contents(dirname(__FILE__) . '/../tests/policies.json'), true);
        } else {
            $policy_data = json_decode(file_get_contents(RAWDATA . '/scrapedjson/policies.json'), true);
        }
        $this->policies = $policy_data['policies'] ?? [];
        $this->set_descs = $policy_data['set_descs'] ?? [];
        $this->sets = $policy_data['sets'] ?? [];

        $this->all_policy_agreements = $policy_data['agreements'] ?? [];

        if ($policy_id) {
            $this->policy_id = $policy_id;
            $this->policies = [
                $policy_id => $this->policies[$policy_id] ?? '',
            ];
        }
    }

    public function getCovidAffected() {
        return $this->covid_affected;
    }

    public function getPolicies() {
        return $this->policies;
    }

    public function getPolicyIDs() {
        return array_keys($this->policies);
    }

    public function getSets() {
        return $this->sets;
    }

    public function getSetDescriptions() {
        return $this->set_descs;
    }

    /**
     * Get Array
     *
     * Return an array of policies.
     *
     * @return array Array of policies in the form `[ {id} , {text} ]`
     */
    public function getPoliciesData() {
        $out = [];
        foreach ($this->policies as $policy_id => $policy_text) {
            $out[] = [
                'id' => $policy_id,
                'text' => $policy_text,
                'commons_only' => in_array($policy_id, $this->commons_only),
            ];
        }
        return $out;
    }

    /**
     * Shuffle
     *
     * Shuffles the list of policy positions.
     *
     * @return self
     */

    public function shuffle() {
        $random = Utility\Shuffle::keyValue($this->policies);

        $new_policies = new self();
        $new_policies->policies = $random;

        return $new_policies;
    }

    /**
     * Limit To Set
     *
     * Limit the policies to those in a set and order accordingly
     *
     * @param string $set The name of the set to use.
     *
     * @return self
     */
    public function limitToSet($set) {

        // Sanity check the set exists
        if (isset($this->sets[$set])) {
            $out = [];
            // Reassemble the new policies list based on the set.
            foreach ($this->sets[$set] as $set_policy) {
                if (isset($this->policies[$set_policy])) {
                    $out[$set_policy] = $this->policies[$set_policy];
                } else {
                    // if we've limited the policies to a single one then we only
                    // want to complain here if we're looking for that policy and
                    // it does not exist. Otherwise, if the single policy isn't in
                    // the set we want to return an empty set
                    if (!isset($this->policy_id) || $set_policy == $this->policy_id) {
                        throw new \Exception('Policy ' . $set_policy . ' in set "' . $set . '" does not exist.');
                    }
                }
            }

            $new_policies = new self($this->policy_id);
            $new_policies->policies = $out;

            return $new_policies->shuffle();

        } else {
            throw new \Exception('Policy set "' . $set . '" does not exist.');
        }
    }

    public function limitToArray($policies) {
        $out = [];
        // Reassemble the new policies list based on the set.
        foreach ($policies as $policy) {
            if (isset($this->policies[$policy])) {
                $out[$policy] = $this->policies[$policy];
            }
        }

        $new_policies = new self();
        $new_policies->policies = $out;

        return $new_policies;
    }

    public function getPoliciesWithFreeVote() {
        $q = $this->db->query(
            "SELECT policy_id
            FROM policies WHERE contains_free_vote = 1",
        );

        $ids = [];
        foreach ($q as $row) {
            $ids[] = $row['policy_id'];
        }

        return $ids;
    }

    public function getPolicyDetails($policyID) {
        $q = $this->db->query(
            "SELECT policy_id, title, description, contains_free_vote, image, image_attrib, image_license, image_license_url, image_source
            FROM policies WHERE policy_id = :policy_id",
            [':policy_id' => $policyID]
        )->first();

        $props = [
            'policy_id' => $q['policy_id'],
            'title' => $q['title'],
            // remove full stops from the end of descriptions. Some of them have them and
            // some of them don't so we enforce consistency here
            'description' => preg_replace('/\. *$/', '', $q['description']),
            'contains_free_vote' => $q['contains_free_vote'],
            'image' => '/images/header-debates-uk.jpg', // TODO: get a better default image
            'image_license' => '',
            'image_attribution' => '',
            'image_source' => '',
            'image_license_url' => '',
        ];

        $image = $q['image'];

        if ($image && file_exists(BASEDIR . '/' . $image)) {
            $props['image'] = $image;
            $props['image_license'] = $q['image_license'];
            $props['image_attribution'] = $q['image_attrib'];
            $props['image_source'] = $q['image_source'];
            $props['image_license_url'] = $q['image_license_url'];
        }

        return $props;
    }

    /**
     * Get annotation counts for all policies for a specific person
     *
     * @param int $personId The person ID to check
     * @return array Array of policy_id => annotation_count
     */
    /**
     * Get annotation counts for all policies for a specific person
     *
     * @param int $personId The person ID to check
     * @return array Array of policy_id => annotation_count
     */
    public function getAnnotationCountsForAllPolicies($personId) {
        $args = [
            ':person_id' => $personId,
        ];

        $result = $this->db->query(
            "SELECT pdl.policy_id, COUNT(*) as annotation_count
            FROM policydivisionlink pdl
            JOIN persondivisionvotes pdv ON pdl.division_id = pdv.division_id
            WHERE pdv.person_id = :person_id 
            AND pdv.annotation IS NOT NULL 
            AND pdv.annotation != ''
            GROUP BY pdl.policy_id",
            $args
        )->fetchAll();

        $annotation_counts = [];
        foreach ($result as $row) {
            $annotation_counts[$row['policy_id']] = $row['annotation_count'];
        }

        return $annotation_counts;
    }

}
