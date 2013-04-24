<?php

namespace NodePub\ThemeEngine\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use NodePub\ThemeEngine\ThemeManager;
use NodePub\ThemeEngine\Controller\ThemeController;
use NodePub\ThemeEngine\Twig\ThemeTwigExtension;

/**
 * Service Provider for Silex integration
 */
class ThemeServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['np.theme.templates.ext'] = 'twig';
        $app['np.theme.templates.custom_css'] = '_styles.css.twig';
        $app['np.theme.mount_point'] = '/np-admin/themes';
        $app['np.theme.active'] = 'default';

        $app['np.theme.settings'] = $app->share(function($app) {
            return array();
        });

        $app['np.theme.custom_settings'] = $app->share(function($app) {
            return array();
        });

        $app['np.theme.global_areas'] = $app->share(function($app) {
            return new ArrayCollection();
        });

        $app['np.theme.areas'] = $app->share(function($app) {
            return new ArrayCollection();
        });
        
        $app['np.theme.manager'] = $app->share(function($app) {
            return new ThemeManager($app['np.theme.paths']);
        });

        $app['np.theme.controller'] = $app->share(function($app) {
            return new ThemeController($app['np.theme.manager'], $app['twig']);
        });
    }

    public function boot(Application $app)
    {
        $app->before(function() use ($app) {
            $app['np.theme.manager']->setTemplateFileExtension($app['np.theme.templates.ext']);
            $app['np.theme.manager']->initialize();

            // iterate over loaded themes and add their path and namespace to Twig
            $themes = $app['np.theme.manager']->loadThemes();
            foreach ($themes as $theme) {
                $app['twig.loader.filesystem']->addPath($theme->getPath(), $theme->getNamespace());
            }

            $app['np.theme.manager']->setActiveTheme($app['np.theme.active']);
            $app['np.theme.manager']->getActiveTheme()->customize($app['np.theme.custom_settings']);

            $app['twig']->addExtension(new ThemeTwigExtension(
                $app['np.theme.manager'],
                $app['np.theme.templates.custom_css']
            ));

            $app['twig']->addGlobal('theme_path', '/themes/'.$app['np.theme.active'].'/');

            $app['twig.loader.filesystem']->addPath(__DIR__.'/../../../../templates', 'theme_admin');
        });

        $themeControllers = $app['controllers_factory'];

        $themeControllers->get('/', 'np.theme.controller:themesAction')
            ->bind('get_themes');

        $themeControllers->get('/{theme}/settings', 'np.theme.controller:settingsAction')
            ->bind('get_theme_settings');

        $themeControllers->post('/{theme}/settings', 'np.theme.controller:postSettingsAction')
            ->bind('post_theme_settings');

        $themeControllers->get('/{theme}/preview/{layout}', 'np.theme.controller:previewAction')
            ->bind('get_theme_preview');

        $app->mount($app['np.theme.mount_point'], $themeControllers);
    }
}
