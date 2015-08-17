<?php

namespace Klio\DB\Tables;

class Users extends \Klio\DB\Table
{

    public function __construct($database)
    {
        parent::__construct($database, 'users');
    }
}
