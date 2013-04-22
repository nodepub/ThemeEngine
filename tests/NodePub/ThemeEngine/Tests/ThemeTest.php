<?php

namespace NodePub\ThemeEngine\Tests;

use NodePub\ThemeEngine\Theme;

class ThemeTest extends \PHPUnit_Framework_TestCase
{
    protected $manager;

    public function testGetName()
    {
        $theme = new Theme(array('name' => 'foo'));
        $this->assertEquals('foo', $theme->getName());
    }

    public function testGetNameIsString()
    {
        $theme = new Theme(array());
        $this->assertEquals('', $theme->getName());
        $this->assertTrue(is_string($theme->getName()));
    }

    public function testGetNamespace()
    {
        $theme = new Theme(array('namespace' => 'foo'));
        $this->assertEquals('foo', $theme->getNamespace());
    }

    public function testGetNamespaceIsString()
    {
        $theme = new Theme(array());
        $this->assertEquals('', $theme->getNamespace());
        $this->assertTrue(is_string($theme->getNamespace()));
    }

    public function testGetSettings()
    {
        $settings = array(
            'foo' => 'bar',
            'bas' => 'bat'
        );

        $theme = new Theme(array('settings' => $settings));
        $this->assertEquals($settings, $theme->getSettings());
    }

    public function testGetSettingsIsArray()
    {
        $theme = new Theme(array());
        $this->assertTrue(is_array($theme->getSettings()));
    }

    public function testGetSettingsKeys()
    {
        $settings = array(
            'foo' => 'bar',
            'bas' => 'bat'
        );

        $theme = new Theme(array('settings' => $settings));
        $this->assertEquals(array('foo', 'bas'), $theme->getSettingsKeys());
    }

    public function testCustomize()
    {
        $settings = array(
            'foo' => 'bar',
            'bas' => 'bat'
        );

        $customSettings = array(
            'foo' => 'custom',
            'fud' => 'new'
        );

        $expectedSettings = array(
            'foo' => 'custom',
            'bas' => 'bat',
            'fud' => 'new'
        );

        $theme = new Theme(array('settings' => $settings));
        $theme->customize($customSettings);

        $this->assertEquals($expectedSettings, $theme->getSettings());
    }

    public function testCustomizeWhenOriginalEmpty()
    {
        $customSettings = array(
            'foo' => 'custom',
            'fud' => 'new'
        );

        $theme = new Theme(array('settings' => array()));
        $theme->customize($customSettings);

        $this->assertEquals($customSettings, $theme->getSettings());
    }

    public function testCustomizeWhenCustomEmpty()
    {
        $settings = array(
            'foo' => 'bar',
            'bas' => 'bat'
        );

        $theme = new Theme(array('settings' => $settings));
        $theme->customize(array());

        $this->assertEquals($settings, $theme->getSettings());
    }
}
