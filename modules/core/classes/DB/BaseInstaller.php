<?php

namespace Klio\DB;

abstract class BaseInstaller
{

    /** @var \Klio\DB\Database */
    protected $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    abstract public function run();
}
