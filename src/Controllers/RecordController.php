<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\App;
use App\DB\Database;
use App\DB\Tables\Permissions;

class RecordController extends Base {

    /**
     * @return \WordPress\Tabulate\Template
     */
    private function get_template($table) {
        $template = new \App\Template('record/admin.twig');
        $template->table = $table;
        return $template;
    }

    public function view(Request $request, Response $response, array $args) {
        $db = new Database();
        $table = $db->getTable($args['table']);
        $template = new \App\Template('record/view.twig');
        $template->title = $table->get_title();
        $template->table = $table;
        $template->record = $table->get_record($args['id']);
        $response->setContent($template->render());
        return $response;
    }

    public function edit(Request $request, Response $response, array $args) {
        // Get database and table.
        $db = new Database();
        $table = $db->getTable($args['table']);
        $template = new \App\Template('record/edit.twig');
        $template->table = $table;
        if (!$table) {
            $template->message(\App\Template::ERROR, "Table {$args['table']} not found.");
            $response->setContent($template->render());
            return $response;
        }

        // Give it all to the template.
        $template->user = $this->user;
        $template->active_tab = 'create';
        $template->title = $table->get_title();
        $template->tables = $db->getTableNames();
        if (isset($args['id'])) {
            $template->record = $table->get_record($args['id']);
            // Check permission.
            if (!$this->user->can(Permissions::UPDATE, $table->get_name())) {
                $template->message(\App\Template::ERROR, 'You do not have permission to update data in this table.');
            }
            $template->active_tab = 'edit';
        }
        if (!isset($template->record) || $template->record === false) {
            $template->record = $table->get_default_record();
            // Check permission.
            if (!$this->user->can(Permissions::READ, $table->get_name())) {
                $template->message(\App\Template::WARNING, 'You do not have permission to read records in this table.');
            }
            // Add query-string values.
            if (isset($args['defaults'])) {
                $template->record->set_multiple($args['defaults']);
            }
        }

        // Don't save to non-updatable views.
        if (!$table->is_updatable()) {
            $template->add_notice('error', "This table can not be updated.");
        }

        // Return to URL.
        if (isset($args['return_to'])) {
            $template->return_to = $args['return_to'];
        }

        $out = $template->render();
        $response->setContent($out);
        return $response;
    }

    public function save(Request $request, Response $response, array $args) {
        $db = new Database();
        $table = $db->getTable($args['table']);
        if (!$table) {
            // It shouldn't be possible to get here via the UI, so no message.
            return false;
        }

        //$record_ident = isset($args['id']) ? $args['id'] : false;
        $template = $this->get_template($table);

        // Make sure we're not saving over an already-existing record.
        $pk_name = $table->get_pk_column()->get_name();
        $pk = $_POST[$pk_name];
        try {
            $db->query("BEGIN");
            $template->record = $table->save_record($_POST, $pk);
            $db->query('COMMIT');
            $template->message('updated', 'Record saved.');
        } catch (\Exception $e) {
            $template->message('error', $e->getMessage(), true);
            $template->record = new \App\DB\Record($table, $_POST);
        }
        // Redirect back to the edit form.
        $return_to = (!empty($_REQUEST['return_to']) ) ? $_REQUEST['return_to'] : $template->record->get_url();
        return $this->redirect($return_to);
    }

    public function delete($args) {
        $db = new \WordPress\Tabulate\DB\Database($this->wpdb);
        $table = $db->getTable($args['table']);
        $record_ident = isset($args['ident']) ? $args['ident'] : false;
        if (!$record_ident) {
            wp_redirect($table->get_url());
            exit;
        }

        // Ask for confirmation.
        if (!isset($_POST['confirm_deletion'])) {
            $template = new \WordPress\Tabulate\Template('record/delete.html');
            $template->table = $table;
            $template->record = $table->get_record($record_ident);
            return $template->render();
        }

        // Delete the record.
        try {
            $this->wpdb->query('BEGIN');
            $table->delete_record($record_ident);
            $this->wpdb->query('COMMIT');
        } catch (\Exception $e) {
            $template = $this->get_template($table);
            $template->record = $table->get_record($record_ident);
            $template->add_notice('error', $e->getMessage());
            return $template->render();
        }

        wp_redirect($table->get_url());
        exit;
    }

}
