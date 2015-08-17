<?php

namespace Klio;

class Users
{

    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        App::addListener(DB\Database::EVENT_GET_TABLE_NAMES, array($this, 'databaseGetTableNames'));
        App::addListener(DB\Column::EVENT_HAS_PERMISSION, array($this, 'columnHasPermission'));
        App::addListener(View::EVENT_INIT, array($this, 'viewInit'));
    }

    public function viewInit(Event $event)
    {
        $userId = \Klio\App::session()->get('user_id');
        if (!$userId) {
            return;
        }
        $settings = new Settings($this->app->getBaseDir());
        $db = new DB\Database($settings->get('database'));
        $user = $db->query('SELECT * FROM users WHERE id = :id', [':id' => $userId])->fetch();
        $event->view->user = $user;
    }

    public function databaseGetTableNames(Event $event)
    {
        if (!in_array('users', $event->table_names)) {
            return false;
        }
        $usersTable = new DB\Tables\Users($event->database);
    }

    public function columnHasPermission(Event $event)
    {
        $column = $event->column;
        $action = $event->action;

        // User not logged in.
        $userId = \Klio\App::session()->get('user_id');
        $nonReadPerms = [
            DB\Column::PERM_CREATE,
            DB\Column::PERM_UPDATE,
            DB\Column::PERM_DELETE,
        ];
        if (!$userId && in_array($action, $nonReadPerms)) {
            $event->permission = false;
            return;
        }

        // 
        
//        $usersTable = new DB\Tables\Users($event->database);
//        if ($usersTable->countRecords() === 0) {
//            
//        }


        $event->permission = false;
    }
}
