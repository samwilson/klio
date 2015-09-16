<?php

namespace App\Controllers;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\DB\Database;
use App\DB\Grants;

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
        
        return $response;
    }

    public function edit(Request $request, Response $response, array $args) {
        // Get database and table.
        $db = new Database();
        $table = $db->getTable($args['table']);
        $template = $this->get_template($table);
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
        if (isset($args['ident'])) {
            $template->record = $table->get_record($args['ident']);
            // Check permission.
            if (!Grants::current_user_can(Grants::UPDATE, $table->get_name())) {
                $template->add_notice('error', 'You do not have permission to update data in this table.');
            }
        }
        if (!isset($template->record) || $template->record === false) {
            $template->record = $table->get_default_record();
            // Check permission.
            if (!$this->user->can(Grants::READ, $table->get_name())) {
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

        $response->setContent($template->render());
        return $response;
    }

    public function save($args) {
        $db = new \WordPress\Tabulate\DB\Database($this->wpdb);
        $table = $db->get_table($args['table']);
        if (!$table) {
            // It shouldn't be possible to get here via the UI, so no message.
            return false;
        }

        // Guard against non-post requests. c.f. wp-comments-post.php
        if (!isset($_SERVER['REQUEST_METHOD']) || 'POST' != $_SERVER['REQUEST_METHOD']) {
            header('Allow: POST');
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: text/plain');
            return false;
        }

        $record_ident = isset($args['ident']) ? $args['ident'] : false;
        $template = $this->get_template($table);

        // Make sure we're not saving over an already-existing record.
        $pk_name = $table->get_pk_column()->get_name();
        $pk = $_POST[$pk_name];
        $existing = $table->get_record($pk);
        if (!$record_ident && $existing) {
            $template->add_notice('updated', "The record identified by '$pk' already exists.");
            $_REQUEST['return_to'] = $existing->get_url();
        } else {
            // Otherwise, create a new one.
            try {
                $data = wp_unslash($_POST);
                $this->wpdb->query('BEGIN');
                $template->record = $table->save_record($data, $record_ident);
                $this->wpdb->query('COMMIT');
                $template->add_notice('updated', 'Record saved.');
            } catch (\Exception $e) {
                $template->add_notice('error', $e->getMessage());
                $template->record = new \WordPress\Tabulate\DB\Record($table, $data);
            }
        }
        // Redirect back to the edit form.
        $return_to = (!empty($_REQUEST['return_to']) ) ? $_REQUEST['return_to'] : $template->record->get_url();
        wp_redirect($return_to);
        exit;
    }

    public function delete($args) {
        $db = new \WordPress\Tabulate\DB\Database($this->wpdb);
        $table = $db->get_table($args['table']);
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
