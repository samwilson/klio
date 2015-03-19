<?php

namespace Klio\Tests;

class SchemaTest extends KlioTestCase
{

    /** @var \Klio\DB\Database */
    protected $db;

    public function setUp()
    {
        parent::setUp();
        $this->db = $this->getDb();
        $sql = 'CREATE TABLE test_table ('
                . ' id INT(10) PRIMARY KEY,'
                . ' title VARCHAR(100) NOT NULL,'
                . ' description TEXT NULL,'
                . ' active BOOLEAN NULL DEFAULT TRUE,'
                . ' a_date DATE NULL,'
                . ' type_id INT(10) NULL DEFAULT NULL'
                . ')';
        $this->db->query($sql);
        $sql = 'CREATE TABLE test_types ('
                . ' id INT(10) PRIMARY KEY,'
                . ' title VARCHAR(100) NOT NULL'
                . ')';
        $this->db->query($sql);
        $this->db->query(
                "ALTER TABLE `test_table`"
                . " ADD FOREIGN KEY ( `type_id` )"
                . " REFERENCES `test_types` (`id`)"
                . " ON DELETE CASCADE ON UPDATE CASCADE;"
        );
    }

    /**
     * @test
     */
    public function tables()
    {
        $this->assertContains('test_table', $this->db->getTableNames());

        // That test_table references test_types
        $testTable = $this->db->getTable('test_table');
        $this->assertEquals('test_types', array_pop($testTable->getReferencedTables(true))->getName());

        $typeTable = $this->db->getTable('test_types');
        $this->assertEquals('test_table', array_pop($typeTable->getReferencingTables())->getName());
    }

    /**
     * @test
     */
    public function nullAndEmptyValues()
    {
        $testTable = $this->db->getTable('test_table');

        // Make sure NULL is reported correctly.
        $this->assertContains('test_table', $this->db->getTableNames());
        $this->assertEquals(false, $testTable->getColumn('title')->isNull());
        $this->assertEquals(true, $testTable->getColumn('description')->isNull());

        // Convert empty strings to NULL for a nullable date column.
        $testTable->saveRecord(array('id' => 1, 'title' => 'Test', 'a_date' => ''));
        $this->assertNull($testTable->getRecord(1)->a_date());
    }

    /**
     * @test
     */
    public function booleans()
    {
        $testTable = $this->db->getTable('test_table');

        // Column type.
        $this->assertTrue($testTable->getColumn('active')->isBoolean());
        $this->assertTrue($testTable->getColumn('active')->isNull());

        // Column values.
        // True.
        $testTable->saveRecord(array('id' => 1, 'active' => true));
        $this->assertTrue($testTable->getRecord(1)->active(), "Can save 'true'.");
        // False.
        $testTable->saveRecord(array('active' => false), 1);
        $this->assertFalse($testTable->getRecord(1)->active(), "Can save 'false'.");
        // Null.
        $testTable->saveRecord(array('active' => null), 1);
        $this->assertNull($testTable->getRecord(1)->active(), "Can save 'null'.");
        // Back to false again.
        $testTable->saveRecord(array('active' => false), 1);
        $this->assertFalse($testTable->getRecord(1)->active());
    }

    /**
     * @test
     */
    public function dates()
    {
        $testTable = $this->db->getTable('test_table');

        // Column type.
        $this->assertEquals("date", $testTable->getColumn('a_date')->getType());
        $this->assertTrue($testTable->getColumn('a_date')->isNull());

        // Values.
        $testTable->saveRecord(array('id' => 1, 'a_date' => '2015-01-12'));
        $this->assertEquals('2015-01-12', $testTable->getRecord(1)->a_date());
    }

    /**
     * @test
     */
    public function getColumnsByType()
    {
        $testTable = $this->db->getTable('test_table');
        $dateCols = $testTable->getColumns('date');
        $this->assertEquals(array('a_date'), array_keys($dateCols));
    }
}
