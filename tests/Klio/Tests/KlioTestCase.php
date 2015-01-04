<?php

namespace Klio\Tests;

abstract class KlioTestCase extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        parent::setUp();
        $this->cleanDb();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->cleanDb();
    }

    /**
     * Drop all tables.
     */
    protected function cleanDb()
    {
        $db = new \Klio\DB\Database(true);
        foreach ($db->getTableNames() as $tbl) {
            $db->query("DROP TABLE $tbl");
        }
    }
}
