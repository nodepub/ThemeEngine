<?php

namespace NodePub\ThemeEngine\Tests\Model;

use NodePub\ThemeEngine\Model\Asset;

class AssetTest extends \PHPUnit_Framework_TestCase
{
    protected $manager;

    public function getWebPathTest()
    {
        $asset = new Asset('/foo/bar/bas/themes/fooTheme/css/foo.css');

        $this->assertEquals(
            '/themes/fooTheme/css/foo.css',
            $asset->getWebDir()
        );
    }
}
