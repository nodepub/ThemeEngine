<?php

namespace NodePub\ThemeEngine\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use NodePub\ThemeEngine\ThemeManager;
use NodePub\ThemeEngine\Theme;
use NodePub\ThemeEngine\Config\ConfigurationProviderInterface;

class ThemeController
{
    protected $themeManager,
              $configurationProvider,
              $twig,
              $formFactory;

    public function __construct(
        ThemeManager $themeManager,
        ConfigurationProviderInterface $configurationProvider,
        $formFactory,
        $twigEnvironment)
    {
        $this->themeManager = $themeManager;
        $this->configurationProvider = $configurationProvider;
        $this->formFactory = $formFactory;
        $this->twig = $twigEnvironment;
    }

    public function themesAction()
    {
        $themes = $this->themeManager->loadThemes();
        $templates = array();

        foreach ($themes as $theme) {
            $templates[$theme->getNamespace()] = $this->themeManager->getThemeLayouts($theme->getNamespace());
        }

        return $this->twig->render('@theme_admin/themes.twig', array(
            'themes' => $themes,
            'templates' => $templates
        ));
    }

    public function settingsAction($theme)
    {
        if (!$theme = $this->themeManager->getTheme($theme)) {
            throw new \Exception("Theme not found", 404);
        }

        return $this->twig->render('@theme_admin/settings.twig', array(
            'theme_name' => $theme->getName(),
            'settings'   => $theme->getSettings(),
            'form'       => $this->getForm($theme)->createView()
        ));
    }

    public function postSettingsAction($theme)
    {
        if (!$theme = $this->themeManager->getTheme($theme)) {
            throw new \Exception("Theme not found", 404);
        }

        $form = $this->getForm($theme)->bind($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $this->configurationProvider->update($theme->getNamespace(), $form->getData());

            // redirect somewhere
            // return $app->redirect('...');
        }
    }

    public function previewAction($theme, $layout)
    {
        $this->twig->addGlobal('theme_path', '/themes/'.$theme.'/');

        $template = sprintf('@%s/%s', $theme, $layout);

        return $this->twig->render('@theme_admin/preview.twig', array(
            'preview_template' => $template
        ));
    }

    protected function getForm(Theme $theme)
    {
        $form = $this->formFactory->createBuilder('form', $theme->getSettings());

        foreach ($theme->getSettingsKeys() as $key) {
            $form->add($key);
        }

        return $form->getForm();
    }
}