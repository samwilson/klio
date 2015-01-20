<?php

namespace Klio;

class Settings
{

    protected static $settings = array();

    public static function get($name, $default = null)
    {
        if (isset(self::$settings[$name])) {
            return self::$settings[$name];
        }
        try {
            $db = new DB\Database();
            $sql = 'SELECT `value` FROM `settings` WHERE `name` = :name';
            $stmt = $db->query($sql, array(':name' => $name));
            $value = $stmt->fetchColumn();
            if (!$value) {
                $value = $default;
            }
            self::$settings[$name] = $value;
            return $value;
        } catch (\PDOException $e) {
            return $default;
        }
    }

    public static function save($name, $value)
    {
        $db = new DB\Database();
        if (self::get($name, false)) {
            $sql = 'UPDATE `settings` SET `value`=:value WHERE `name`=:name';
        } else {
            $sql = 'INSERT INTO `settings` (`name`,`value`) VALUES (:name, :value)';
        }
        $res = $db->query($sql, array(':name' => $name, ':value' => $value));
        self::$settings[$name] = $value;
    }

    public static function siteTitle()
    {
        return self::get('site_title', \Klio\App::name());
    }

    public static function recordsPerPage()
    {
        return self::get('records_per_page', 10);
    }
}
