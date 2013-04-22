<?php

namespace NodePub\ThemeEngine\Controller;

use Silex\Application;

class ThemeController
{
    function themesAction(Application $app)
    {
        $themeConfigs = $app['theme.manager']->loadThemes();
        $templates = array();
        $namespaces = array();

        foreach ($themeConfigs as $theme) {
            $templates[$theme->getName()] = $app['theme.manager']->getThemeLayouts($theme->getNamespace());
            $namespaces[$theme->getName()] = $theme->getNamespace();
        }

        return $app['twig']->render('@app/themes.twig', array(
            'themes' => $templates,
            'namespaces' => $namespaces
        ));
    }

    function settingsAction(Application $app, $theme)
    {
        if (!$theme = $app['theme.manager']->getTheme($theme)) {
            throw new \Exception("Theme not found", 404);
        }

        return $app['twig']->render('@app/settings.twig', array(
            'theme_name' => $theme->getName(),
            'settings'   => $theme->getSettings()
        ));
    }

    function postSettingsAction(Application $app, $theme)
    {
        if (!$theme = $app['theme.manager']->getTheme($theme)) {
            throw new \Exception("Theme not found", 404);
        }
    }

    function previewAction(Application $app, $theme, $layout)
    {
        $app['twig']->addGlobal('theme_path', '/themes/'.$theme.'/');

        $template = sprintf('@%s/%s', $theme, $layout);

        return $app['twig']->render('@app/preview.twig', array(
            'preview_template' => $template
        ));
    }
}