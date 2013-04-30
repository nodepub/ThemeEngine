<?php

namespace NodePub\ThemeEngine\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use NodePub\ThemeEngine\ThemeManager;
use NodePub\ThemeEngine\Theme;
use NodePub\ThemeEngine\Config\ConfigurationProviderInterface;

class ThemeController
{
    protected $themeManager,
              $configurationProvider,
              $app;

    public function __construct(
        ThemeManager $themeManager,
        ConfigurationProviderInterface $configurationProvider,
        Application $app)
    {
        $this->themeManager = $themeManager;
        $this->configurationProvider = $configurationProvider;
        $this->app = $app;
    }

    public function themesAction()
    {
        $themes = $this->themeManager->loadThemes();
        $templates = array();

        foreach ($themes as $theme) {
            $templates[$theme->getNamespace()] = $this->themeManager->getThemeLayouts($theme->getNamespace());
        }

        return $this->app['twig']->render('@theme_admin/themes.twig', array(
            'themes' => $themes,
            'templates' => $templates
        ));
    }

    public function settingsAction(Theme $theme)
    {
        return $this->app['twig']->render('@theme_admin/settings.twig', array(
            'active_theme' => $theme,
            'form'  => $this->getForm($theme)->createView()
        ));
    }

    public function postSettingsAction(Request $request, Theme $theme)
    {
        $form = $this->getForm($theme)->bind($request);

        if ($form->isValid()) {

            // only save the values that differ from the default
            $customSettings = $theme->getCustomSettings($form->getData());

            if ($this->configurationProvider->update($theme->getNamespace(), $customSettings)) {
                $this->app['session']->getFlashBag()->add('success', 'Theme Settings were saved.');
                return $this->app->redirect($this->app['url_generator']->generate('get_themes'));
            } else {
                $this->app['session']->getFlashBag()->add('error', 'An error prevented Theme Settings from being saved. Check log for details.');
                return $this->app->redirect(
                    $this->app['url_generator']->generate(
                        'get_theme_settings',
                        array('theme' => $theme->getNamespace())
                    )
                );
            }
        } else {
            $this->app['session']->getFlashBag()->add('error', 'The form contains errors, please see below.');
            return $this->app['twig']->render('@theme_admin/settings.twig', array(
                'active_theme' => $theme,
                'form'  => $this->getForm($theme)->createView()
            ));
        }
    }

    public function previewAction(Theme $theme, $layout)
    {
        $this->app['twig']->addGlobal('theme_path', '/themes/'.$theme->getNamespace().'/');

        $template = sprintf('@%s/%s', $theme->getNamespace(), $layout);

        return $this->app['twig']->render('@theme_admin/preview.twig', array(
            'preview_template' => $template
        ));
    }

    public function postPreviewAction(Theme $theme)
    {
        $this->app['twig']->addGlobal('theme_path', '/themes/'.$theme->getNamespace().'/');
        $this->app['session']->set('theme_preview', $theme->getNamespace());

        return $this->app->redirect('/');
    }

    protected function getForm(Theme $theme)
    {
        $form = $this->app['form.factory']->createBuilder('form', $theme->getSettings());

        foreach ($theme->getSettingsKeys() as $key) {
            $form->add($key);
        }

        return $form->getForm();
    }
}