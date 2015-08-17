<?php

namespace Klio\Tests;

/**
 * @group modules
 * @group modules.users
 */
class UserTest extends KlioTestCase
{

    public function setUp()
    {
        parent::setUp();
        $db = $this->getDb();
        $db->install($this->getBaseDir());
        $sql = 'CREATE TABLE test_table ('
            . ' id INT(10) PRIMARY KEY,'
            . ' title VARCHAR(100) NOT NULL,'
            . ' description TEXT NULL,'
            . ' active BOOLEAN NULL DEFAULT TRUE,'
            . ' a_date DATE NULL,'
            . ' type_id INT(10) NULL DEFAULT NULL'
            . ')';
        $db->query($sql);
    }

    /**
     * @testdox By default, anyone can view anything, and authenticated users can edit anything.
     * @test
     */
    public function basic()
    {
        // Make sure the correct tables are there, and we get them in the correct type.
        $this->assertInstanceOf('\\Klio\\DB\\Table', $this->getDb()->getTable('permissions'));
        $users = $this->getDb()->getTable('users');
        $this->assertInstanceOf('\\Klio\\DB\\Tables\\Users', $users);
        // User #1 is created on install, and called 'admin'.
        $admin = $users->getRecord(1);
        $this->assertInstanceOf('\\Klio\\DB\\Records\\Users', $admin);
        $this->assertEquals('admin', $admin->username());
        // Group #1 is the admin group. @TODO change this?
        $this->assertTrue($admin->isAdmin());
        // Create another user, and they won't be an admin.
        $users->saveRecord(['username' => 'test']);
        $this->assertTrue($admin->isAdmin());

        // Anon read-only access.
//        $testTable = $this->getDb()->getTable('test_table');
//        $this->assertTrue($testTable->can(\Klio\DB\Column::PERM_READ));
//        $this->assertFalse($testTable->can(\Klio\DB\Column::PERM_CREATE));
        // Logged-in user can create and edit.
//        $userData = array(
//            'username' => 'testuser',
//        );
//        \Klio\App::session()->set('user_id', 1);
//        //$user = $this->getDb()->getTable('users')->saveRecord($userData);
//        $this->assertFalse($testTable->can(\Klio\DB\Column::PERM_CREATE));
    }
}
