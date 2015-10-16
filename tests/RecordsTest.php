<?php

namespace App\Tests;

class RecordsTest extends Base {

    /**
     * @testdox Getting the *FKTITLE() variant of a foreign key returns the title of the foreign record.
     * @test
     */
    public function related() {
        $testTable = $this->db->getTable('test_table');
        $typeRec = $this->db->getTable('test_types')->save_record(array('title' => 'Type 1'));
        $dataRec = $testTable->save_record(array('title' => 'Rec 1', 'type_id' => $typeRec->id()));
        $this->assertEquals('Type 1', $dataRec->type_idFKTITLE());
        $referecingRecs = $typeRec->get_referencing_records($testTable, 'type_id');
        $this->assertCount(1, $referecingRecs);
        $referecingRec = array_pop($referecingRecs);
        $this->assertEquals('Rec 1', $referecingRec->title());
        $referenced = $testTable->get_referenced_tables();
        $this->assertArrayHasKey('type_id', $referenced);
        $this->assertEquals('test_types', $referenced['type_id']);
    }

    /**
     * @testdox Where there is no unique column, the 'title' is just the foreign key.
     * @test
     */
    public function titles() {
        $test_table = $this->db->getTable('test_table');
        $this->assertEmpty($test_table->get_unique_columns());
        $this->assertEquals('id', $test_table->get_title_column()->get_name());
        $rec = $test_table->save_record(array('title' => 'Rec 1', 'description' => 'Lorem ipsum.'));
        $this->assertEquals('[ 1 | Rec 1 | Lorem ipsum. | 1 |  |  |  | 5.60 |  ]', $rec->get_title());
    }

    /**
     * @testdox Set-membership (IS IN) filters can be applied.
     * @test
     */
    public function filters() {
        $testTypes = $this->db->getTable('test_types');
        for ($i = 0; $i<100; $i++) {
            $testTypes->save_record(['title' => "Type $i"]);
        }
        $this->assertEquals(100, $testTypes->count_records());
        
        // Three particular types.
        $testTypes->add_filter('title', 'in', "Type 10\nType 20\nType 30");
        $this->assertEquals(3, $testTypes->count_records());

        // Everything apart from four particular ones.
        $testTypes->reset_filters();
        $testTypes->add_filter('id', 'not in', "40\n41\n42\n43");
        $this->assertEquals(96, $testTypes->count_records());
    }

}
