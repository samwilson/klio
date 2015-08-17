<?php

namespace Klio\Tests;

abstract class KlioTestCase extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        parent::setUp();
        $this->cleanDb();
        $this->app = new \Klio\App($this->getBaseDir(), '');
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->cleanDb();
    }

    protected function getBaseDir()
    {
        return realpath(__DIR__ . '/../../..');
    }

    protected function getDb()
    {
        $settings = new \Klio\Settings($this->getBaseDir());
        return new \Klio\DB\Database($settings->get('database_test'));
    }

    /**
     * Drop all tables.
     */
    protected function cleanDb()
    {
        $db = $this->getDb();
        $db->query("SET FOREIGN_KEY_CHECKS = 0");
        foreach ($db->getTableNames() as $tbl) {
            $db->query("DROP TABLE $tbl");
        }
        $db->query("SET FOREIGN_KEY_CHECKS = 1");
    }
}
