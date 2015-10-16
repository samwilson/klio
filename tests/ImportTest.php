<?php

namespace App\Tests;

class ImportTest extends Base {

    /**
     * Save some CSV data to a file, and create a quasi-$_FILES entry for it.
     * @param string $data
     * @return string|array
     */
    private function save_data_file($data) {
        $test_filename = sys_get_temp_dir() . '/test_' . uniqid() . '.csv';
        file_put_contents($test_filename, $data);
        $uploaded = array(
            'type' => 'text/csv',
            'file' => $test_filename,
        );
        return $uploaded;
    }

    /**
     * @testdox Rows can be imported from CSV.
     * @test
     */
    public function basic_import() {
        $testtypes_table = $this->db->getTable('test_types');
        $csv = '"ID","Title"' . "\r\n"
                . '"1","One"' . "\r\n"
                . '"2","Two"' . "\r\n";
        $uploaded = $this->save_data_file($csv);
        $csv = new \App\CSV(null, $uploaded);
        $csv->load_data();
        $column_map = array('title' => 'Title');
        $csv->import_data($this->db, $testtypes_table, $column_map);
        // Make sure 2 records were imported.
        $this->assertEquals(2, $testtypes_table->count_records());
        $rec1 = $testtypes_table->get_record(1);
        $this->assertEquals('One', $rec1->title());
        // And that 1 changeset was created, with 4 changes.
        //$change_tracker = new \App\DB\ChangeTracker($this->db);
        $sql1 = "SELECT COUNT(id) FROM `changesets`";
        $this->assertEquals(1, $this->db->query($sql1)->fetchColumn());
        $sql2 = "SELECT COUNT(id) FROM `changes`";
        $this->assertEquals(4, $this->db->query($sql2)->fetchColumn());
    }

    /**
     * @testdox Import rows that specify an existing PK will update existing records.
     * @test
     */
    public function primary_key() {
        $testtable = $this->db->getTable('test_table');
        $rec1 = $testtable->save_record(array('title' => 'PK Test'));
        $this->assertEquals(1, $testtable->count_records());
        $this->assertNull($rec1->description());

        // Add a field's value.
        $csv = '"ID","Title","Description"' . "\r\n"
                . '"1","One","A description"' . "\r\n";
        $uploaded = $this->save_data_file($csv);
        $csv = new \App\CSV(null, $uploaded);
        $csv->load_data();
        $column_map = array('id' => 'ID', 'title' => 'Title', 'description' => 'Description');
        $csv->import_data($this->db, $testtable, $column_map);
        // Make sure there's still only one record, and that it's been updated.
        $this->assertEquals(1, $testtable->count_records());
        $rec2 = $testtable->get_record(1);
        $this->assertEquals('One', $rec2->title());
        $this->assertEquals('A description', $rec2->description());

        // Leave out a required field.
        $csv = '"ID","Description"' . "\r\n"
                . '"1","New description"' . "\r\n";
        $uploaded2 = $this->save_data_file($csv);
        $csv2 = new \App\CSV(null, $uploaded2);
        $csv2->load_data();
        $column_map2 = array('id' => 'ID', 'description' => 'Description');
        $csv2->import_data($this->db, $testtable, $column_map2);
        // Make sure there's still only one record, and that it's been updated.
        $this->assertEquals(1, $testtable->count_records());
        $rec3 = $testtable->get_record(1);
        $this->assertEquals('One', $rec3->title());
        $this->assertEquals('New description', $rec3->description());
    }

}
