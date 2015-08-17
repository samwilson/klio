<?php

namespace Klio;

/**
 * This is a very general event class.
 */
class Event extends \Symfony\Component\EventDispatcher\Event
{

    public $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }
}
