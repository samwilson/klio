<?php

namespace Klio\Tests;

class SchemaTest extends KlioTestCase
{

    /**
     * @test
     */
    public function nullAndEmptyValues()
    {
        $db = new \Klio\DB\Database(true);
        $sql = 'CREATE TABLE test_table ('
                . ' id INT(10) PRIMARY KEY,'
                . ' title VARCHAR(100) NOT NULL,'
                . ' description TEXT NULL,'
                . ' a_date DATE NULL'
                . ')';
        $db->query($sql);
        $testTable = $db->getTable('test_table');

        // Make sure NULL is reported correctly.
        $this->assertContains('test_table', $db->getTableNames());
        $this->assertEquals(false, $testTable->getColumn('title')->isNull());
        $this->assertEquals(true, $testTable->getColumn('description')->isNull());

        // Convert empty strings to NULL for a nullable date column.
        $testTable->saveRecord(array('id' => 1, 'title' => 'Test', 'a_date' => ''));
        $this->assertNull($testTable->getRecord(1)->a_date());
    }
}
