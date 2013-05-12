<?php

namespace NodePub\ThemeEngine\Twig;

use NodePub\ThemeEngine\ThemeManager;
use NodePub\ThemeEngine\Model\Asset;
use NodePub\ThemeEngine\Helper\AssetHelper;

class ThemeTwigExtension extends \Twig_Extension
{
    protected $themeManager,
              $customCssTemplateName,
              $minifyAssets;

    protected $twigEnvironment;

    public function __construct(ThemeManager $themeManager, $customCssTemplateName, $minifyAssets = true)
    {
        $this->themeManager = $themeManager;
        $this->customCssTemplateName = $customCssTemplateName;
        $this->minifyAssets = $minifyAssets;
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

    public function themeStyles()
    {
        $theme = $this->themeManager->getActiveTheme();
        $linkTag = '<link rel="stylesheet" href="%s">';
        $stylesheets = $theme->getStylesheetAssets();
        $stylesheetLinks = array();

        if (true === $this->minifyAssets) {
            $stylesheetLinks[] = sprintf($linkTag, $this->getThemesPath().$theme->getNamespace().AssetHelper::PATH_MIN_CSS);
        } else {
            foreach ($stylesheets as $asset) {
                $stylesheetLinks[] = sprintf($linkTag, $asset->getWebPath());
            }
        }

        return implode(PHP_EOL, $stylesheetLinks) . PHP_EOL . $this->getCustomizedCss() . PHP_EOL;
    }

    public function themeJavaScripts()
    {
        $theme = $this->themeManager->getActiveTheme();
        $scriptTag = '<script src="%s"></script>';
        $scripts = $theme->getJavaScriptAssets();
        $scriptLinks = array();

        if (true === $this->minifyAssets) {
            $scriptLinks[] = sprintf($scriptTag, $this->getThemesPath().$theme->getNamespace().AssetHelper::PATH_MIN_JS);
        } else {
            foreach ($scripts as $asset) {
                $scriptLinks[] = sprintf($scriptTag, $asset->getWebPath());
            } 
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
