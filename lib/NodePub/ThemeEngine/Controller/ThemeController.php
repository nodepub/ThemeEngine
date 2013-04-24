<?php

namespace NodePub\ThemeEngine\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use NodePub\ThemeEngine\ThemeManager;

class ThemeController
{
    /**
     * @var ThemeManager
     */
    protected $themeManager;

    protected $twigEngine;

    public function __construct(ThemeManager $themeManager, $twigEngine)
    {
        $this->themeManager = $themeManager;
        $this->twigEngine = $twigEngine;
    }

    function themesAction()
    {
        $themeConfigs = $this->themeManager->loadThemes();
        $templates = array();
        $namespaces = array();

        foreach ($themeConfigs as $theme) {
            $templates[$theme->getName()] = $this->themeManager->getThemeLayouts($theme->getNamespace());
            $namespaces[$theme->getName()] = $theme->getNamespace();
        }

        return $this->twigEngine->render('@theme_admin/themes.twig', array(
            'themes' => $templates,
            'namespaces' => $namespaces
        ));
    }

    function settingsAction($theme)
    {
        if (!$theme = $this->themeManager->getTheme($theme)) {
            throw new \Exception("Theme not found", 404);
        }

        return $this->twigEngine->render('@theme_admin/settings.twig', array(
            'theme_name' => $theme->getName(),
            'settings'   => $theme->getSettings()
        ));
    }

    function postSettingsAction($theme)
    {
        if (!$theme = $this->themeManager->getTheme($theme)) {
            throw new \Exception("Theme not found", 404);
        }
    }

    function previewAction($theme, $layout)
    {
        $this->twigEngine->addGlobal('theme_path', '/themes/'.$theme.'/');

        $template = sprintf('@%s/%s', $theme, $layout);

        return $this->twigEngine->render('@theme_admin/preview.twig', array(
            'preview_template' => $template
        ));
    }
}