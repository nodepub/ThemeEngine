<?php

namespace NodePub\ThemeEngine;

class TwigExtension extends \Twig_Extension
{
    public $themeManager;
    public $customCssTemplateName;

    protected $twigEnvironment;

    public function getName()
    {
        return 'NodePubThemeEngine';
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->twigEnvironment = $environment;
    }
    
    // public function getGlobals()
    // {
    //     return array(
    //         'theme_path' => 'themes/default/';
    //     );
    // }
    
    public function getFunctions()
    {
        return array(
            'theme_styles'   => new \Twig_Function_Method($this, 'themeStyles'),
        );
    }

    public function getCustomizedCss()
    {
        return $this->twigEnvironment->render(
            '@default/_styles.css.twig',
            $this->themeManager->getCurrentTheme()->getSettings()
        );
    }

    public function themeStyles($themeName = null)
    {
        $globals = $this->twigEnvironment->getGlobals();
        $themePath = isset($globals['theme_path']) ? $globals['theme_path'] : '/';

        $stylesheetLinks = array();
        $stylesheets = $this->themeManager->getCurrentTheme()->getStylesheets();
        foreach ($stylesheets as $stylesheet) {
            $stylesheetLinks[] = sprintf('<link rel="stylesheet" href="%scss/%s">', $themePath, $stylesheet);
        }

        return implode(PHP_EOL, $stylesheetLinks) . PHP_EOL . $this->getCustomizedCss() . PHP_EOL;
    }
}
