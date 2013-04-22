<?php

namespace NodePub\ThemeEngine\Tests;

class TwigExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testThemeStyles()
    {
        $envMock = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->setMethods(array('getGlobals'))
            ->getMock();

        $envMock->expects($this->once())
            ->method('getGlobals')
            ->will($this->returnValue(array('theme_path' => '/foo/bar/')));

        $themeMock = $this->getMockBuilder('NodePub\ThemeEngine\Theme')
            ->disableOriginalConstructor()
            ->setMethods(array('getStylesheets'))
            ->getMock();

        $themeMock->expects($this->once())
            ->method('getStylesheets')
            ->will($this->returnValue(array('one.css', 'two.css', 'three.css')));

        $themeManagerMock = $this->getMockBuilder('NodePub\ThemeEngine\ThemeManager')
            ->disableOriginalConstructor()
            ->setMethods(array('getCurrentTheme'))
            ->getMock();

        $themeManagerMock->expects($this->once())
            ->method('getCurrentTheme')
            ->will($this->returnValue($themeMock));

        $extMock = $this->getMock('NodePub\ThemeEngine\TwigExtension', array('getCustomizedCss'));

        $extMock->expects($this->once())
            ->method('getCustomizedCss')
            ->will($this->returnValue('CUSTOM_CSS'));

        $extMock->initRuntime($envMock);
        $extMock->themeManager = $themeManagerMock;

        $expected = 
            '<link rel="stylesheet" href="/foo/bar/css/one.css">'.PHP_EOL
            .'<link rel="stylesheet" href="/foo/bar/css/two.css">'.PHP_EOL
            .'<link rel="stylesheet" href="/foo/bar/css/three.css">'.PHP_EOL
            .'CUSTOM_CSS'.PHP_EOL
        ;

        $this->assertEquals($expected, $extMock->themeStyles());
    }
}
