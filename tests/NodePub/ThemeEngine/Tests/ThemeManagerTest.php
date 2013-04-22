<?php

namespace NodePub\ThemeEngine\Tests;

use NodePub\ThemeEngine\ThemeManager;
use Doctrine\Common\Collections\ArrayCollection;

class ThemeManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $manager;

    public function setup()
    {
        $this->manager = new ThemeManager(THEMES_DIR);
    }

    public function testFindThemeDirectories()
    {
        $this->assertEquals(2, iterator_count($this->manager->findThemeDirectories()));
    }

    public function testLoadThemes()
    {
        $themes = $this->manager->loadThemes();

        $this->assertEquals(2, count($themes));
        $this->assertEquals('Default NodePub', $themes[0]->getName());
        $this->assertEquals('Metropolis', $themes[1]->getName());

        // Check that path is added to config info
        $this->assertTrue(is_string($themes[0]->getPath()));
    }

    public function testGetThemeLayouts()
    {
        $this->manager->initialize();
        $layouts = $this->manager->getThemeLayouts('default');

        $this->assertEquals(5, count($layouts));
        $this->assertTrue(in_array('layout.twig', $layouts));
        $this->assertFalse(in_array('_post.twig', $layouts));
    }
}
