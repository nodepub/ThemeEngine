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
            'theme_styles'      => new \Twig_Function_Method($this, 'themeStyles'),
            'theme_javascripts' => new \Twig_Function_Method($this, 'themeJavaScripts'),
            'body_class'        => new \Twig_Function_Method($this, 'bodyClass'),
        );
    }

    public function themeStyles($themeName = null)
    {
        $globals = $this->twigEnvironment->getGlobals();
        $linkTag = '<link rel="stylesheet" href="%s%s">';
        $stylesheets = $$this->themeManager->getActiveTheme()->getStylesheetPaths();
        $stylesheetLinks = array();

        if (isset($globals['app']['debug'])) {
            foreach ($stylesheets as $stylesheet) {
                $stylesheetLinks[] = sprintf($linkTag, $this->getThemesPath(), $stylesheet);
            }
        } else {
            $stylesheetLinks[] = sprintf($linkTag, $this->getThemesPath(), '/css/styles.min.css');
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

    public function bodyClass()
    {
        $globals = $this->twigEnvironment->getGlobals();
        $classes = array();

        if (isset($globals['section'])) {
            $classes[]= 'section_' . $globals['section'];
        }

        if (isset($globals['page_title'])) {
            $classes[]= $this->slugify($globals['page_title']);
        }

        return implode(' ', $classes);
    }

    public function slugify($string)
    {
        return str_replace(' ', '-', strtolower($string));
    }

    protected function getCustomizedCss()
    {
        return $this->twigEnvironment->render(
            $this->customCssTemplateName,
            $this->themeManager->getActiveTheme()->getSettings()
        );
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
