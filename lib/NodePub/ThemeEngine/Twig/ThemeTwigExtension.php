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
            'theme_javascripts' => new \Twig_Function_Method($this, 'themeJavaScripts'),
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
        $stylesheetLinks = array();
        $stylesheets = $this->themeManager->getActiveTheme()->getStylesheetPaths();
        foreach ($stylesheets as $stylesheet) {
            $stylesheetLinks[] = sprintf('<link rel="stylesheet" href="%s%s">', $this->getThemesPath(), $stylesheet);
        }

        return implode(PHP_EOL, $stylesheetLinks) . PHP_EOL . $this->getCustomizedCss() . PHP_EOL;
    }

    public function themeJavaScripts()
    {
        $scriptLinks = array();
        $scripts = $this->themeManager->getActiveTheme()->getJavaScriptPaths();
        foreach ($scripts as $script) {
            $scriptLinks[] = sprintf('<script src="%s%s"></script>', $this->getThemesPath(), $script);
        }

        return implode(PHP_EOL, $scriptLinks);
    }

    /**
     * Returns the root relative path of the themes directory
     */
    protected function getThemesPath()
    {
        $globals = $this->twigEnvironment->getGlobals();
        return isset($globals['themes_path']) ? $globals['themes_path'] : '/themes/';
    }
}
