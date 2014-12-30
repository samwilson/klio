<?php

namespace Klio;

class Arr
{

    public static function get($array, $key, $default)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
