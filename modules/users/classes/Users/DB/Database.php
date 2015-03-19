<?php

namespace Klio\Users\DB;

class Database
{

    public function handle(\Klio\DB\Event $event)
    {
        if ($event->db->getTable('permissions', false)) {
            $event->db->query("SELECT * FROM permissions");
        }
        //$event->data = array('users');
    }
}
