<?php

namespace App;

class App {

    /**
     * Get the application's name.
     * @return string
     */
    public static function name() {
        return 'Klio';
    }

    /**
     * Get the application's version.
     * Conforms to Semantic Versioning guidelines.
     * @link http://semver.org
     * @return string
     */
    public static function version() {
        return '0.1.0';
    }

    /**
     * Get the site's base URL. Never has a trailing slash.
     * @return string
     */
    public static function baseurl() {
        $baseurl = self::env('BASEURL', '/klio');
        return rtrim($baseurl, '/');
    }

    public static function url($path = '') {
        return self::baseurl() . '/' . ltrim($path, '/');
    }

    public static function mode() {
        return self::env('MODE', 'production');
    }

    public static function env($name, $default = null) {
        $env = getenv($name);
        return ($env) ? $env : $default;
    }

    /**
     * Dump a variable with the Symfony VarDumper (only if in development mode).
     * @param mixed $var
     */
    public static function dump() {
        if (self::mode() !== 'production') {
            foreach (func_get_args() as $var) {
                \Symfony\Component\VarDumper\VarDumper::dump($var);
            }
        }
    }

    public static function snakeCase($str) {
        $res = preg_replace_callback('|[A-Z]{1,}|', function ($m) {
            return '_' . reset($m);
        }, $str);
        $res = trim($res, '_');
        return mb_strtolower($res);
    }

    /**
     * Turn a spaced or underscored string to camelcase (with no spaces or underscores).
     *
     * @param string $str
     * @return string
     */
    public static function camelcase($str) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }

    /**
     * Apply the titlecase filter to a string: removing underscores, uppercasing
     * initial letters, and performing a few common (and not-so-common) word
     * replacements such as initialisms and punctuation.
     *
     * @param string|array $value    The underscored and lowercase string to be
     *                               titlecased, or an array of such strings.
     * @param 'html'|'latex' $format The desired output format.
     * @return string                A properly-typeset title.
     * @todo Get replacement strings from configuration file.
     */
    public static function titlecase($value, $format = 'html') {

        /**
         * The mapping of words (and initialisms, etc.) to their titlecased
         * counterparts for HTML output.
         * @var array
         */
        $html_replacements = array(
            'id' => 'ID',
            'cant' => "can't",
            'in' => 'in',
            'at' => 'at',
            'of' => 'of',
            'for' => 'for',
            'sql' => 'SQL',
            'todays' => "Today's",
        );

        /**
         * The mapping of words (and initialisms, etc.) to their titlecased
         * counterparts for LaTeX output.
         * @var array
         */
        $latex_replacements = array(
            'cant' => "can't",
        );

        /**
         * Marshall the correct replacement strings.
         */
        if ($format == 'latex') {
            $replacements = array_merge($html_replacements, $latex_replacements);
        } else {
            $replacements = $html_replacements;
        }

        /**
         * Recurse if neccessary
         */
        if (is_array($value)) {
            return array_map(array(self, 'titlecase'), $value);
        } else {
            $out = ucwords(preg_replace('|_|', ' ', $value));
            foreach ($replacements as $search => $replacement) {
                $out = preg_replace("|\b$search\b|i", $replacement, $out);
            }
            return trim($out);
        }
    }

}
