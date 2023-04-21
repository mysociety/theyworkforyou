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

class AnnoucementManagement extends Banner
// We're extending Banner so we can dump json into the current banner system
{

    public function set_text($text)
    {
        // check text is valid json
        $json_obj = json_decode($text);

        if (!$json_obj) {
            return false;
        }

        return parent::set_text($text);
    }

    private function get_json()
    {
        // get the json from the database
        $use_json = false;

        if ($use_json) {
            $json_str = file_get_contents(__DIR__ . "/announcements.json");
        } else {
            $json_str = $this->get_text();
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
        $json_obj = $this->get_json();

        if (!$json_obj) {
            return null;
        }

        $banners = $json_obj->banners;

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
        // get annoucements stored in json
        $json_obj = $this->get_json();

        if (!$json_obj) {
            return null;
        }

        $items = $json_obj->items;

        # discard any annoucements where published is false
        $items = array_filter($items, function ($item) {
            return $item->published;
        });

        # limit to annoucements with the correct location
        $items = array_filter($items, function ($item) use ($location) {
            return in_array($location, $item->location) || in_array("all", $item->location);
        });

        # if none left return null
        if (count($items) == 0) {
            return null;
        }

        return select_based_on_weight($items);
    }
}
?>
