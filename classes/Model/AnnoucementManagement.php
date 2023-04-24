<?php
/**
 * AnnoucementManagement Model
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou\Model;

function select_based_on_weight($items)
{
    # banners have a weight attribute, which is the probability of being selected
    # the higher the weight, the higher the probability
    $total_weight = 0;
    foreach ($items as $item) {
        $total_weight += $item->weight;
    }

    $random_number = rand(0, $total_weight);

    $current_weight = 0;
    foreach ($items as $item) {
        $current_weight += $item->weight;
        if ($random_number <= $current_weight) {
            return $item;
        }
    }
}

class AnnoucementManagement
// We're extending Banner so we can dump json into the current banner system
{

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
        $this->db = new \ParlDB;
        $this->mem = new \MySociety\TheyWorkForYou\Memcache();
    }

    public function get_text($editoral_option) {
        $text = $this->mem->get($editoral_option);

        if ( $text === false ) {
            $q = $this->db->query("SELECT value FROM editorial WHERE item = :editoral_option", array(":editoral_option" => $editoral_option))->first();

            if ($q) {
                $text = $q['value'];
                if ( trim($text) == '' ) {
                    $text = NULL;
                }
                $this->mem->set($editoral_option, $text, 86400);
            }
        }

        return $text;
    }

    public function set_text($text, $editoral_option) {
        $q = $this->db->query("REPLACE INTO editorial set item = :editoral_option, value = :banner_text",
            array(
                ':banner_text' => $text,
                ':editoral_option' => $editoral_option
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


    public function set_json($text, $editoral_option)
    {
        // check text is valid json
        $json_obj = json_decode($text);

        if (!$json_obj) {
            return false;
        }

        return $this->set_text($text, $editoral_option);
    }

    private function get_json($editoral_option)
    {
        // for debugging, can use json files instead of db
        $use_json_file = false;

        if ($use_json_file) {
            $json_str = file_get_contents(__DIR__ . "/announcements/" . $editoral_option . ".json");
        } else {
            $json_str = $this->get_text($editoral_option);
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
        $banners  = $this->get_json("banner");

        if (!$banners ) {
            return null;
        }

        # discard any banners where published is false
        $banners = array_filter($banners, function ($banner) {
            return $banner->published;
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

        # discard any announcements where published is false
        $items = array_filter($items, function ($item) {
            return $item->published;
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
