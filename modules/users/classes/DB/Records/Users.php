<?php

namespace Klio\DB\Records;

class Users extends \Klio\DB\Record
{

    public function isAdmin()
    {
        $sql = 'SELECT COUNT(*) AS `tot` '
            . ' FROM `users_to_groups` '
            . ' WHERE `user` = :uid AND `group` =  1';
        $query = $this->table->getDatabase()
            ->query($sql, [':uid' => $this->id()])
            ->fetch();
        return ($query->tot > 0);
    }
}
