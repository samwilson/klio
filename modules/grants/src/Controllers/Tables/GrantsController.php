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
        $template->addPath('modules/grants/templates');
        $template->title = 'Grants';
        $template->user = $this->user;
        $template->tables = $db->getTableNames();
        $template->tableNames = $db->getTableNames(false);
        $template->groups = $db->getTable('groups', false)->get_records(false);
        $grants = new \App\DB\Grants();
        $template->capabilities = $grants->get_capabilities();
        $response->setContent($template->render());
        return $response;
    }

}
