<?php

namespace App\Tests;

class ExportTest extends Base {

    /**
     * @testdox A table can be exported to CSV.
     * @test
     */
    public function basic_export() {
        // Let the current user do anything.
        global $current_user;
        $current_user->add_cap('promote_users');

        // Add some data to the table.
        $test_table = $this->db->getTable('test_types');
        $test_table->save_record(array('title' => 'One'));
        $test_table->save_record(array('title' => 'Two'));
        $filename = $test_table->export();
        $this->assertFileExists($filename);
        $csv = '"ID","Title"' . "\r\n"
                . '"1","One"' . "\r\n"
                . '"2","Two"' . "\r\n";
        $this->assertEquals($csv, file_get_contents($filename));
    }

    /**
     * @testdox Point colums are exported as WKT.
     * @test
     */
    public function point_wkt() {
        $this->wpdb->query('DROP TABLE IF EXISTS `point_export_test`');
        $this->wpdb->query('CREATE TABLE `point_export_test` ('
                . ' id INT(10) AUTO_INCREMENT PRIMARY KEY,'
                . ' title VARCHAR(100) NOT NULL,'
                . ' geo_loc POINT NOT NULL'
                . ');'
        );
        $db = new WordPress\Tabulate\DB\Database($this->wpdb);
        $test_table = $db->getTable('point_export_test');
        $test_table->save_record(array('title' => 'Test', 'geo_loc' => 'POINT(10.1 20.2)'));
        $filename = $test_table->export();
        $this->assertFileExists($filename);
        $csv = '"ID","Title","Geo Loc"' . "\r\n"
                . '"1","Test","POINT(10.1 20.2)"' . "\r\n";
        $this->assertEquals($csv, file_get_contents($filename));
    }

}
