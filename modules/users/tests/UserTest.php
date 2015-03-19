<?php

namespace Klio\Tests;

/**
 * @group modules
 * @group modules.userss
 */
class UserTest extends KlioTestCase
{

    public function setUp()
    {
        parent::setUp();
        $db = $this->getDb();
        $db->install($this->getBaseDir());
    }

    /**
     * @test
     */
    public function basic()
    {
        $this->assertInstanceOf('Klio\DB\Table', $this->getDb()->getTable('permissions'));
    }
}
