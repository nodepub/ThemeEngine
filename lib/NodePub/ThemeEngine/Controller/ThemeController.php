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

    public function themesAction(Request $request)
    {
        $themes = $this->themeManager->loadThemes();
        $templates = array();

        foreach ($themes as $theme) {
            $templates[$theme->getNamespace()] = $this->themeManager->getThemeLayouts($theme->getNamespace());
        }

        return $this->app['twig']->render('@theme_admin/themes.twig', array(
            'layout' => $this->app['np.admin.template'],
            'themes' => $themes,
            'templates' => $templates,
            'referer' => urlencode($request->getPathInfo())
        ));
    }

    public function settingsAction(Theme $theme)
    {
        $this->themeManager->activateTheme($theme);

        return $this->app['twig']->render('@theme_admin/settings.twig', array(
            'layout' => $this->app['np.admin.template'],
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
                'layout' => $this->app['np.admin.template'],
                'active_theme' => $theme,
                'form'  => $this->getForm($theme)->createView()
            ));
        }
    }

    public function previewLayoutAction(Theme $theme, $layout)
    {
        $template = sprintf('@%s/%s', $theme->getNamespace(), $layout);

        return $this->app['twig']->render('@theme_admin/preview.twig', array(
            'layout' => $template
        ));
    }

    public function switchThemeAction(Request $request, $referer)
    {
        $defaultFormData = array(
            'referer' => $referer
        );

        if ($previouslySelectedTheme = $this->app['session']->get('theme_preview')) {
            $defaultFormData['theme'] = $previouslySelectedTheme;
        }

        $form = $this->app['form.factory']->createBuilder('form', $defaultFormData)
            ->add('referer', 'hidden')
            ->add('theme', 'choice', array(
                'choices' => $this->themeManager->getActiveThemeNames(),
                'expanded' => false,
                'label' => 'Theme Preview'
            ))
            ->getForm();

        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $data = $form->getData();

                if (isset($data['cancel'])) {
                    $this->app['session']->remove('theme_preview');
                } else {
                    // Save selected theme to session
                    $theme = $this->themeManager->getTheme($data['theme']);
                    $this->app['session']->set('theme_preview', $theme->getNamespace());
                }

                return $this->app->redirect(urldecode($data['referer']));
            }
        }

        // display the form
        return $this->app['twig']->render('@theme_admin/_theme_switcher.twig', array('form' => $form->createView()));
    }

    public function resetThemeAction(Request $request, $referer)
    {
        $this->app['session']->remove('theme_preview');
        return $this->app->redirect(urldecode($referer));
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