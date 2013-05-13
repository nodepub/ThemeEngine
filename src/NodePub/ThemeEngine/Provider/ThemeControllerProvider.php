<?php

namespace NodePub\ThemeEngine\Provider;

use Silex\Application;
use Silex\ControllerProviderInterface;

class ThemeControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $themeProvider = function($namespace) use($app) {
            if (!$theme = $app['np.theme.manager']->getTheme($namespace)) {
                throw new \Exception("Theme not found", 404);
            }

            return $theme;
        };

        $controllers->get('/', 'np.theme.controller:themesAction')
            ->bind('get_themes');

        $controllers->get('/{theme}/settings', 'np.theme.controller:settingsAction')
            ->convert('theme', $themeProvider)
            ->bind('get_theme_settings');

        $controllers->post('/{theme}/settings', 'np.theme.controller:postSettingsAction')
            ->convert('theme', $themeProvider)
            ->bind('post_theme_settings');

        $controllers->get('/{theme}/preview/{layout}', 'np.theme.controller:previewLayoutAction')
            ->convert('theme', $themeProvider)
            ->bind('get_theme_preview');

        $controllers->match('/switcher/referer/{referer}', 'np.theme.controller:switchThemeAction')
            ->value('referer', '')
            ->bind('theme_switcher');

        return $controllers;
    }
}