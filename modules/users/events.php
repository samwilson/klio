<?php

$events = new E
return array(
    \Klio\DB\Database::EVENT_GET_TABLE_NAMES => 'Klio\Users\EventHandlers\Database',
    \Klio\Controller::EVENT_GET_VIEW => 'Klio\Users\EventHandlers\Controller',
    \Klio\DB\Column::EVENT_HAS_PERMISSION => 'Klio\Users\EventHandlers\Permission',
);
