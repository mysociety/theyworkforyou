<?php

namespace MySociety\TheyWorkForYou\Model;

class AnnoucementManagement {

    public function get_random_valid_banner(){
        # these are temp functions to test frontend
        $json_str = file_get_contents(__DIR__  . '/announcements.json');
        $json_obj = json_decode($json_str);

        $banners = $json_obj->banners;

        # discard any banners where published is false
        $banners = array_filter($banners, function($banner){
            return $banner->published;
        });

        # if none left return null
        if(count($banners) == 0){
            return null;
        }

        # banners have a weight attribute, which is the probability of being selected
        # the higher the weight, the higher the probability
        $total_weight = 0;
        foreach($banners as $banner){
            $total_weight += $banner->weight;
        }

        $random_number = rand(0, $total_weight);

        $current_weight = 0;
        foreach($banners as $banner){
            $current_weight += $banner->weight;
            if($random_number <= $current_weight){
                return $banner;
            }
        }

    }

    public function get_random_valid_annoucement(){
        # these are temp functions to test frontend
        $json_str = file_get_contents(__DIR__  . '/announcements.json');
        $json_obj = json_decode($json_str);

        $annoucements = $json_obj->annoucements;

        # discard any annoucements where published is false
        $annoucements = array_filter($annoucements, function($annoucement){
            return $annoucement->published;
        });

        # if none left return null
        if(count($annoucements) == 0){
            return null;
        }

        # annoucements have a weight attribute, which is the probability of being selected
        # the higher the weight, the higher the probability
        $total_weight = 0;

        foreach($annoucements as $annoucement){
            $total_weight += $annoucement->weight;
        }




    }



}

?>
