<?php

namespace Klio;

class Arr
{

    public static function get($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * Find the Longest Common Prefix of two strings.
     *
     * @param string $str1
     * @param string $str2
     * @return string The LCP.
     */
    public static function lcp($str1, $str2)
    {
        $prefix = "";
        $length = min(strlen($str1), strlen($str2));
        for ($l = 0; $l <= $length; $l++) {
            if ($str1[$l] != $str2[$l]) {
                return substr($str1, 0, $l);
            }
//            $substr = substr($str1, 0, $l);
//            if ($substr == substr($str2, 0, $l)) {
//                $prefix = $substr;
//            } else {
//                break;
//            }
        }
        return $prefix;
    }

    /**
     * Find all LCPs (over a certain length) in an array.
     *
     * This is a bit like phpMyAdmin's `List_Database::getGroupedDetails()`
     * method, except that one only uses the *first* underscore as the prefix
     * separator; here, we use the maximum length possible (but that still ends
     * in an underscore).
     *
     * @param array $arr
     * @param integer $min_length
     * @return array[string]
     */
    public static function getPrefixGroups($arr, $min_length = 4)
    {
        $out = array();
        asort($arr);
        $arr = array_values($arr);
        for ($str1_idx = 0; $str1_idx < count($arr); $str1_idx++) {
            for ($str2_idx = 0; $str2_idx < count($arr); $str2_idx++) {
                $str1 = $arr[$str1_idx];
                $str2 = $arr[$str2_idx];

                $lcp = self::lcp($str1, $str2);

                // $prev is the length of the LCP of: (str1, and the element before str1 if there is one).
                $prev = 0;
                if (isset($arr[$str1_idx - 1])) {
                    $prev_lcp = self::lcp($str1, $arr[$str1_idx - 1]);
                    $prev = strlen($prev_lcp);
                }

                // $next is the length of the LCP of: (str1, and the element after str1 if there is one).
                $next = 0;
                if (isset($arr[$str1_idx + 1])) {
                    $next_lcp = self::lcp($str1, $arr[$str1_idx + 1]);
                    $next = strlen($next_lcp);
                }

                // 'is_other' and 'is_self' are with respect to str1 and str2.
                $is_long_enough = strlen($lcp) > $min_length;
                $is_self = $lcp == $str1;
                $hasCommonNeighbours = $prev > $min_length or $next > $min_length;
                $isSuperstringOfPrev = $prev > $min_length and strpos($lcp, $prev_lcp) !== false;
                $isSuperstringOfNext = $next > $min_length and strpos($lcp, $next_lcp) !== false;
                $not_in_result = !in_array($lcp, $out);
                $is_superstring_of_existing = false;
                foreach ($out as $i) {
                    if (strpos($lcp, $i) !== false and strlen($lcp) > $min_length) {
                        $is_superstring_of_existing = true;
                        continue;
                    }
                }
                $ends_in_underscore = substr($lcp, -1) == '_';

                // Put it all together.
                if ($is_long_enough and $not_in_result and ! $is_superstring_of_existing and $ends_in_underscore
                        and ( (!$is_self and $hasCommonNeighbours and ! $isSuperstringOfPrev and $isSuperstringOfNext)
                        or ( $is_self and ! $hasCommonNeighbours and ! $isSuperstringOfPrev and ! $isSuperstringOfNext)
                        or ( !$is_self and $hasCommonNeighbours and $isSuperstringOfPrev and $isSuperstringOfNext))) {
                    $out[] = $lcp;
                }
            }
        }
        return $out;
    }
}
