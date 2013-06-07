<?php

namespace NodePub\ThemeEngine\Controller;

use NodePub\ThemeEngine\ThemeManager;
use NodePub\ThemeEngine\Theme;
use NodePub\ThemeEngine\Config\ConfigurationProviderInterface;
use NodePub\ThemeEngine\Form\Type\ThemeSettingsType;
use NodePub\ThemeEngine\Helper\AssetHelper;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

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
        $loadedThemes = $this->themeManager->loadThemes();
        $activeTheme = $this->themeManager->getActiveTheme();
        $themes = array($activeTheme); // add active first
        $templates = array();

        foreach ($loadedThemes as $theme) {
            $templates[$theme->getNamespace()] = $this->themeManager->getThemeLayouts($theme->getNamespace());
            if ($activeTheme->getNamespace() !== $theme->getNamespace()) {
                $themes[]= $theme;
            }
        }

        return $this->app['twig']->render('@theme_admin/themes.twig', array(
            'layout' => $this->app['np.admin.template'],
            'standalone' => false,
            'themes' => $themes,
            'templates' => $templates,
            'referer' => urlencode($request->getPathInfo()),
            'active_theme' => $activeTheme->getNamespace()
        ));
    }

    public function settingsAction(Theme $theme)
    {
        $this->themeManager->activateTheme($theme);

        return $this->app['twig']->render('@theme_admin/settings.twig', array(
            'layout' => $this->app['np.admin.template'],
            'standalone' => false,
            'active_theme' => $theme,
            'form'  => $this->getSettingsForm($theme)->createView()
        ));
    }

    public function postSettingsAction(Request $request, Theme $theme)
    {
        $form = $this->getSettingsForm($theme)->bind($request);

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

    public function getPreviewAction(Theme $theme, $url)
    {
        $this->app['session']->set('theme_preview', $theme->getNamespace());
        return $this->app->redirect(urldecode($url));
    }

    /**
     * Sets the preview theme using the session flash, so that it's only
     * set for the next page view.
     */
    public function getPreviewUrlAction(Theme $theme, $url)
    {
        $this->app['session']->set('theme_temp_preview', $theme->getNamespace());
        return $this->app->redirect(urldecode($url));
    }

    public function switchThemeAction(Request $request, $referer)
    {
        $defaultFormData = array(
            'referer' => $referer
        );

        if ($previouslySelectedTheme = $this->app['session']->get('theme_preview')) {
            $defaultFormData['theme'] = $previouslySelectedTheme;
            $resetAttrs = array('class' => 'btn');
        } else {
            $defaultFormData['theme'] = $this->themeManager->getActiveTheme()->getNamespace();
            $resetAttrs = array('disabled' => true, 'class' => 'btn');
        }

        $form = $this->app['form.factory']->createBuilder('form', $defaultFormData)
            ->add('referer', 'hidden')
            ->add('theme', 'choice', array(
                'choices' => $this->themeManager->getActiveThemeNames(),
                'expanded' => false,
                'label' => 'Theme Preview',
                'attr' => array('class' => '-themeSwitchSelect')
            ))
            ->add('reset', 'submit', array('attr' => $resetAttrs))
            ->add('submit', 'submit', array('attr' => array('class' => 'btn')))
            ->getForm();

        if ('POST' === $request->getMethod()) {
            $form->bind($request);

            if ($form->isValid()) {
                $data = $form->getData();

                if ($form->get('reset')->isClicked()) {
                    $this->app['session']->remove('theme_preview');
                } else {
                    // Save selected theme to session
                    $theme = $this->themeManager->getTheme($data['theme']);
                    $this->app['session']->set('theme_preview', $theme->getNamespace());
                }

                return $this->app->redirect(urldecode($data['referer']));
            }
        }

        // display the form on GET
        return $this->app['twig']->render('@theme_admin/_theme_switcher.twig',
            array(
                'form' => $form->createView(),
                'embed_styles' => true
            ));
    }

    public function resetThemeAction(Request $request, $referer)
    {
        $this->app['session']->remove('theme_preview');
        return $this->app->redirect(urldecode($referer));
    }

    public function postActivateAction(Theme $theme)
    {
        // TODO: save the theme for the current site

        return $this->app->json(array('message' => 'Theme activated.'));
    }

    public function minifyStylesheetsAction(Theme $theme)
    {
        $assetHelper = new AssetHelper($theme);
        $css = $assetHelper->compileStylesheets();

        $response = new Response($css);
        $response->headers->set('Content-Type', 'text/css');

        return $response;
    }

    public function minifyJavascriptsAction(Theme $theme)
    {
        $assetHelper = new AssetHelper($theme);
        $js = $assetHelper->compileJavaScripts();

        $response = new Response($js);
        $response->headers->set('Content-Type', 'application/javascript');

        return $response;
    }

    protected function getSettingsForm(Theme $theme)
    {
        return $this->app['form.factory']->create(
            new ThemeSettingsType($theme, $this->app['np.theme.fontstack_provider'])
        );
    }
}