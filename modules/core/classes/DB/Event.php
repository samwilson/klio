<?php

namespace Klio\DB;

/**
 * This is a very general event class that contains a single item of data.
 */
class Event extends \Symfony\Component\EventDispatcher\Event
{

    /** @var \Klio\DB\Database */
    public $db;
    public $data;

    public function __construct($db, $data)
    {
        $this->db = $db;
        $this->data = $data;
    }
}
