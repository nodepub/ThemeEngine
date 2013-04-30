<?php

namespace NodePub\ThemeEngine\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use NodePub\ThemeEngine\ThemeManager;
use NodePub\ThemeEngine\Controller\ThemeController;
use NodePub\ThemeEngine\Twig\ThemeTwigExtension;
use NodePub\ThemeEngine\Config\YamlConfigurationProvider;

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

        $app['np.theme.configuration_provider'] = $app->share(function($app) {
            return new YamlConfigurationProvider($app['np.theme.custom_settings_file']);
        });

        $app['np.theme.custom_settings'] = $app->share(function($app) {
            return $app['np.theme.configuration_provider']->get($app['np.theme.active']);
        });

        $app['np.theme.global_areas'] = $app->share(function($app) {
            return new ArrayCollection();
        });

        $app['np.theme.areas'] = $app->share(function($app) {
            return new ArrayCollection();
        });
        
        $app['np.theme.manager'] = $app->share(function($app) {
            return new ThemeManager($app['np.theme.paths'], $app['dispatcher']);
        });

        $app['np.theme.controller'] = $app->share(function($app) {
            return new ThemeController(
                $app['np.theme.manager'],
                $app['np.theme.configuration_provider'],
                $app
            );
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

            $app['np.theme.manager']->activateTheme($app['np.theme.active']);

            // set active theme's parent
            $theme = $app['np.theme.manager']->getActiveTheme();
            if ($parentName = $theme->getParentNamespace()) {
                if ($parent = $app['np.theme.manager']->getTheme($parentName)) {
                    $theme->setParent($parent);
                }
            }

            if (!empty($app['np.theme.custom_settings'])) {
                $app['np.theme.manager']->getActiveTheme()->customize($app['np.theme.custom_settings']);
            }

            $app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
                $twig->addGlobal('themes_path', '/themes/');
                $twig->addGlobal('active_theme', $app['np.theme.active']);
                $twig->addGlobal('standalone', true); // TODO: this will determine if we're in the larger NP app
                $twig->addExtension(new ThemeTwigExtension(
                    $app['np.theme.manager'],
                    $app['np.theme.templates.custom_css']
                ));

                return $twig;
            }));

            // @TODO This is ugly, full NodePub app will need a way to add admin modules
            $app['twig.loader.filesystem']->addPath(__DIR__.'/../../../../templates', 'theme_admin');
        });

        # ===================================================== #
        #    ROUTES                                             #
        # ===================================================== #

        $themeProvider = function($namespace) use($app) {
            if (!$theme = $app['np.theme.manager']->getTheme($namespace)) {
                throw new \Exception("Theme not found", 404);
            }

            return $theme;
        };

        $themeControllers = $app['controllers_factory'];

        $themeControllers->get('/', 'np.theme.controller:themesAction')
            ->bind('get_themes');

        $themeControllers->get('/{theme}/settings', 'np.theme.controller:settingsAction')
            ->convert('theme', $themeProvider)
            ->bind('get_theme_settings');

        $themeControllers->post('/{theme}/settings', 'np.theme.controller:postSettingsAction')
            ->convert('theme', $themeProvider)
            ->bind('post_theme_settings');

        $themeControllers->get('/{theme}/preview/{layout}', 'np.theme.controller:previewAction')
            ->convert('theme', $themeProvider)
            ->bind('get_theme_preview');

        $themeControllers->post('/{theme}/preview', 'np.theme.controller:postPreviewAction')
            ->convert('theme', $themeProvider)
            ->bind('post_theme_preview');

        $app->mount($app['np.theme.mount_point'], $themeControllers);
    }
}
