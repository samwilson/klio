<?php

namespace Klio;

class Settings
{

    public static function get($name, $default = NULL)
    {
        try {
            $db = new DB\Database();
            $sql = 'SELECT `value` FROM `settings` WHERE `name` = :name';
            $stmt = $db->query($sql, array(':name' => $name));
            $value = $stmt->fetchColumn();
            if (!$value) {
                return $default;
            }
            return $value;
        } catch (\PDOException $e) {
            return $default;
        }
    }

    public static function save($name, $value)
    {
        $db = new DB\Database();
        if (self::get($name, FALSE)) {
            $sql = 'UPDATE `settings` SET `value`=:value WHERE `name`=:name';
        } else {
            $sql = 'INSERT INTO `settings` (`name`,`value`) VALUES (:name, :value)';
        }
        $res = $db->query($sql, array(':name' => $name, ':value' => $value));
    }

    public static function siteTitle()
    {
        return self::get('site_title', \Klio::name());
    }
}
