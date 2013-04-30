<?php

namespace NodePub\ThemeEngine\Twig;

use NodePub\ThemeEngine\ThemeManager;

class ThemeTwigExtension extends \Twig_Extension
{
    protected $themeManager;
    protected $customCssTemplateName;

    protected $twigEnvironment;

    public function __construct(ThemeManager $themeManager, $customCssTemplateName)
    {
        $this->themeManager = $themeManager;
        $this->customCssTemplateName = $customCssTemplateName;
    }

    public function getName()
    {
        return 'NodePubThemeEngine';
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->twigEnvironment = $environment;
    }
    
    public function getFunctions()
    {
        return array(
            'theme_styles' => new \Twig_Function_Method($this, 'themeStyles'),
        );
    }

    public function getCustomizedCss()
    {
        return $this->twigEnvironment->render(
            $this->customCssTemplateName,
            $this->themeManager->getActiveTheme()->getSettings()
        );
    }

    public function themeStyles($themeName = null)
    {
        $globals = $this->twigEnvironment->getGlobals();
        
        $themePath = isset($globals['themes_path']) ? $globals['themes_path'] : '/themes/';

        $stylesheetLinks = array();
        $stylesheets = $this->themeManager->getActiveTheme()->getStylesheets();
        foreach ($stylesheets as $stylesheet) {
            $stylesheetLinks[] = sprintf('<link rel="stylesheet" href="%s%s">', $themePath, $stylesheet);
        }

        return implode(PHP_EOL, $stylesheetLinks) . PHP_EOL . $this->getCustomizedCss() . PHP_EOL;
    }
}
