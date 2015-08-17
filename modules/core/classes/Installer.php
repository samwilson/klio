<?php

namespace Klio;

abstract class Installer
{

    /** @var \Klio\DB\Database */
    protected $db;

    public function __construct(\Klio\DB\Database $db)
    {
        $this->db = $db;
    }

    abstract public function run();
}
