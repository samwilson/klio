<?php

namespace App\Tests;

class ExportTest extends Base {

    /**
     * @testdox A table can be exported to CSV.
     * @test
     */
    public function basic_export() {

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
        $this->db->query('DROP TABLE IF EXISTS `point_export_test`');
        $this->db->query('CREATE TABLE `point_export_test` ('
                . ' id INT(10) AUTO_INCREMENT PRIMARY KEY,'
                . ' title VARCHAR(100) NOT NULL,'
                . ' geo_loc POINT NOT NULL'
                . ');');
        $this->db->query("INSERT INTO grants (`permission`, `group`, `table_name`) VALUES "
                . "(" . \App\DB\Tables\Permissions::READ . ", " . \App\DB\User::PUBLIC_GROUP_ID . ", 'point_export_test'),"
                . "(" . \App\DB\Tables\Permissions::CREATE . ", " . \App\DB\User::PUBLIC_GROUP_ID . ", 'point_export_test');");
        $this->db->getTableNames(false, true);
        $test_table = $this->db->getTable('point_export_test');
        $test_table->save_record(array('title' => 'Test', 'geo_loc' => 'POINT(10.1 20.2)'));
        $filename = $test_table->export();
        $this->assertFileExists($filename);
        $csv = '"ID","Title","Geo Loc"' . "\r\n"
                . '"1","Test","POINT(10.1 20.2)"' . "\r\n";
        $this->assertEquals($csv, file_get_contents($filename));
    }

}
