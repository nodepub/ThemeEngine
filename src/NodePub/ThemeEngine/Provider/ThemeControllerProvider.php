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
            ->bind('admin_themes');

        $controllers->get('/{theme}/settings', 'np.theme.controller:settingsAction')
            ->convert('theme', $themeProvider)
            ->bind('theme_settings');

        $controllers->post('/{theme}/settings', 'np.theme.controller:postSettingsAction')
            ->convert('theme', $themeProvider)
            ->bind('post_theme_settings');

        $controllers->get('/{theme}/preview/{url}', 'np.theme.controller:getPreviewAction')
            ->convert('theme', $themeProvider)
            ->value('url', urlencode('/'))
            ->bind('theme_preview');

        $controllers->get('/{theme}/preview-url/{url}', 'np.theme.controller:getPreviewUrlAction')
            ->convert('theme', $themeProvider)
            ->value('url', urlencode('/'))
            ->bind('theme_preview_url');

        $controllers->get('/{theme}/preview-layout/{layout}', 'np.theme.controller:previewLayoutAction')
            ->convert('theme', $themeProvider)
            ->bind('theme_preview_layout');

        $controllers->post('/{theme}/activate', 'np.theme.controller:postActivateAction')
            ->convert('theme', $themeProvider)
            ->bind('post_theme_activate');

        $controllers->match('/switcher/referer/{referer}', 'np.theme.controller:switchThemeAction')
            ->value('referer', '')
            ->bind('theme_switcher');

        return $controllers;
    }
}