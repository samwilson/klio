<?php

namespace SWFW;

class Str
{

    /**
     * Turn a spaced or underscored string to camelcase (with no spaces or underscores).
     * 
     * @param string $str
     * @return string
     */
    public static function camelcase($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }
}
