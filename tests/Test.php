<?php

class Test extends PHPUnit_Framework_TestCase
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
        $db = new Klio\DB\Database(TRUE);
        foreach ($db->getTableNames() as $tbl) {
            $db->query("DROP TABLE $tbl");
        }
    }

    /**
     * @test
     */
    public function install()
    {
        $db = new Klio\DB\Database(TRUE);
        $this->assertEmpty($db->getTableNames());
        $db->install();
        $this->assertContains('settings', $db->getTableNames(), 'settings table not found');
    }

    /**
     * @test
     */
    public function settings()
    {
        $db = new Klio\DB\Database(TRUE);
        $db->install();
        $this->assertEquals('The Default', \Klio\Settings::get('does_not_exist', 'The Default'));
        \Klio\Settings::save('new_setting', 'New Value');
        $this->assertEquals('New Value', \Klio\Settings::get('new_setting'));
        \Klio\Settings::save('new_setting', 'Changed Value');
        $this->assertEquals('Changed Value', \Klio\Settings::get('new_setting'));
    }
}
