<?php

namespace MySociety\TheyWorkForYou\Utility;

class Shuffle
{
    public static function keyValue($arr) {
        $keys = array_keys($arr);
        shuffle($keys);
        $new = array();
        foreach ($keys as $key) {
            $new[$key] = $arr[$key];
        }
        return $new;
    }
}
