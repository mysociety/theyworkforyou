<?php
/**
 * AnnouncementManagement Model
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\Model;

function is_valid_item($item)
{
    // used in an array sort to filter out invalid items

    // set default language
    if (isset($item->lang)) {
        $language = $item->lang;
    } else {
        $language = "en";
    }

    // set default for published
    if (isset($item->published)) {
        $published = $item->published;
    } else {
        $published = true;
    }

    // set default start_time (in the past)
    if (isset($item->start_time)) {
        $start_time = $item->start_time;
    } else {
        $start_time = "2000-01-01T00:00:00";
    }

    // set default end_time (in the future)
    if (isset($item->end_time)) {
        $end_time = $item->end_time;
    } else {
        $end_time = "2100-01-01T00:00:00";
    }

    return $published &&
        $language == LANGUAGE &&
        $start_time < date("Y-m-d\TH:i:s") &&
        $end_time > date("Y-m-d\TH:i:s");
}

function select_based_on_weight($items)
{
    # banners have a weight attribute, which is the probability of being selected
    # the higher the weight, the higher the probability
    $total_weight = 0;
    foreach ($items as $item) {
        $total_weight += $item->weight;
    }

    $random_number = rand(1, $total_weight);

    $current_weight = 0;
    foreach ($items as $item) {
        $current_weight += $item->weight;
        if ($random_number <= $current_weight) {
            return $item;
        }
    }
}

class AnnouncementManagement
{
    // Multi-purpose announcement storage system
    // Builds on previous banner system, but extends to
    // other locations for announcements
    /**
     * DB handle
     */
    private $db;

    /*
     * Memcache handle
     */
    private $mem;

    public function __construct()
    {
        $this->db = new \ParlDB();
        $this->mem = new \MySociety\TheyWorkForYou\Memcache();
    }

    public function get_text($editorial_option)
    {
        $text = $this->mem->get($editorial_option);

        if ($text === false) {
            $q = $this->db
                ->query(
                    "SELECT value FROM editorial WHERE item = :editorial_option",
                    [":editorial_option" => $editorial_option]
                )
                ->first();

            if ($q) {
                $text = $q["value"];
                if (trim($text) == "") {
                    $text = null;
                }
                $this->mem->set($editorial_option, $text, 86400);
            }
        }

        return $text;
    }

    public function set_text($text, $editorial_option)
    {
        $q = $this->db->query(
            "REPLACE INTO editorial set item = :editorial_option, value = :announcement_text",
            [
                ":announcement_text" => $text,
                ":editorial_option" => $editorial_option,
            ]
        );

        if ($q->success()) {
            if (trim($text) == "") {
                $text = null;
            }
            $this->mem->set($editorial_option, $text, 86400);
            return true;
        }
        return false;
    }

    public function set_json($text, $editorial_option)
    {
        // check text is valid json
        $json_obj = json_decode($text);

        if (!$json_obj) {
            return false;
        }

        return $this->set_text($text, $editorial_option);
    }

    private function get_json($editorial_option)
    {
        // for debugging, can use json files instead of db
        $use_json_file = false;

        if ($use_json_file) {
            $json_str = file_get_contents(
                __DIR__ . "/announcements/" . $editorial_option . ".json"
            );
        } else {
            $json_str = $this->get_text($editorial_option);
        }

        if (!$json_str) {
            return null;
        }

        $json_obj = json_decode($json_str);
        return $json_obj;
    }

    public function get_random_valid_banner()
    {
        // get banners stored in json
        $banners = $this->get_json("banner");

        if (!$banners) {
            return null;
        }

        # discard any invalid banners
        $banners = array_filter($banners, function ($banner) {
            return is_valid_item($banner);
        });

        # if none left return null
        if (count($banners) == 0) {
            return null;
        }

        return select_based_on_weight($banners);
    }

    public function get_random_valid_item($location)
    {
        // get announcements stored in json
        $items = $this->get_json("announcements");

        if (!$items) {
            return null;
        }

        # discard any invalid announcements
        $items = array_filter($items, function ($item) {
            return is_valid_item($item);
        });

        # limit to announcements with the correct location
        $items = array_filter($items, function ($item) use ($location) {
            return in_array($location, $item->location);
        });

        # if none left return null
        if (count($items) == 0) {
            return null;
        }

        return select_based_on_weight($items);
    }
}
?>
