<?php

namespace Klio;

class Users
{

    public function __construct()
    {
        App::$eventDispatcher->addListener(DB\Events::DATABASE_GET_TABLE, array($this, 'getTable'));
    }

    public function getTable(DB\Event $data)
    {
        
    }
}
