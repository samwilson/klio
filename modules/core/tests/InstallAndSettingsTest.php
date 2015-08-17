<?php

namespace Klio\Tests;

class InstallAndSettingsTest extends KlioTestCase
{

    /**
     * @test
     */
    public function install()
    {
        $db = $this->getDb();
        $this->assertEmpty($db->getTableNames());
        $db->install($this->getBaseDir());
        $this->assertContains('settings', $db->getTableNames(), 'settings table not found');
    }

//    /**
//     * @test
//     */
//    public function settings()
//    {
//        $db = $this->getDb();
//        $db->install();
//        $this->assertEquals('The Default', \Klio\Settings::get('does_not_exist', 'The Default'));
//        \Klio\Settings::save('new_setting', 'New Value');
//        $this->assertEquals('New Value', \Klio\Settings::get('new_setting'));
//        \Klio\Settings::save('new_setting', 'Changed Value');
//        $this->assertEquals('Changed Value', \Klio\Settings::get('new_setting'));
//    }
}
