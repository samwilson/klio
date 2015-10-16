<?php

namespace App\Controllers\Tables;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\DB\Database;

class GrantsController extends \App\Controllers\TableController {

    public function edit(Request $request, Response $response, array $args) {
        $user = new \App\DB\User();
        if (!$user->isAdmin()) {
            throw new \League\Route\Http\Exception\NotFoundException();
        }
        $db = new Database();
        $template = new \App\Template('grants.twig');
        //$template->addPath('modules/grants/templates');
        $template->title = 'Grants';
        $template->user = $this->user;
        $template->tables = $db->getTableNames();
        $template->tableNames = $db->getTableNames(false);
        $template->groups = $db->getTable('groups', false)->get_records(false);
        $grants = new \App\DB\Grants();
        $template->grants = $db->getTable('grants', false)->get_records(false);
        //$template->grants = $grants->get();
        $template->capabilities = 
        $response->setContent($template->render());
        return $response;
    }

    public function save(Request $request, Response $response, array $args) {
        $db = new Database();
        $db->query("DELETE FROM `grants`");
        $sql = '';
        foreach ($_POST as $table => $grants) {
            foreach ($grants as $grant => $groupIds) {
                foreach ($groupIds as $groupId) {
                    $sql .= "('$table','$grant',$groupId),";
                }
            }
        }
        $sql = 'INSERT INTO `grants` (`table_name`,`grant`,`group`) VALUES ' . rtrim($sql, ',');
        $db->query($sql);
        return $this->redirect('table/grants/edit');
    }

}
