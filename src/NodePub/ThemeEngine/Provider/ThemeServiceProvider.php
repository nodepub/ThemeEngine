<?php

namespace NodePub\ThemeEngine\Provider;

use NodePub\ThemeEngine\Provider\ThemeControllerProvider;
use NodePub\ThemeEngine\ThemeManager;
use NodePub\ThemeEngine\Controller\ThemeController;
use NodePub\ThemeEngine\Twig\ThemeTwigExtension;
use NodePub\ThemeEngine\Config\YamlConfigurationProvider;
use NodePub\ThemeEngine\Model\Asset;
use NodePub\ThemeEngine\Helper\AssetHelper;

use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service Provider for Silex integration
 */
class ThemeServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['np.theme.templates.ext'] = 'twig';
        $app['np.theme.templates.custom_css'] = '_styles.css.twig';
        $app['np.theme.default'] = 'default';
        $app['np.theme.minify_assets'] = !$app['debug'];

        $app['np.theme.active'] = $app->share(function($app) {

            // One-off page previews
            if ($theme = $app['session']->get('theme_temp_preview')) {
                $app['session']->remove('theme_temp_preview');
                return $theme;
            }

            return $app['session']->get('theme_preview') ?: $app['np.theme.default'];
        });

        $app['np.theme.mount_point'] = $app->share(function($app) {
            $mountPoint = '/themes';
            if (isset($app['np.admin.mount_point'])) {
                $mountPoint = $app['np.admin.mount_point'] . $mountPoint;
            }
            return $mountPoint;
        });

        $app['np.theme.settings'] = $app->share(function($app) {
            return array();
        });

        $app['np.theme.configuration_provider'] = $app->share(function($app) {
            return new YamlConfigurationProvider($app['np.theme.custom_settings_file']);
        });

        $app['np.theme.custom_settings'] = $app->share(function($app) {
            return $app['np.theme.configuration_provider']->get($app['np.theme.active']);
        });

        $app['np.theme.asset_cache_dir'] = $app->share(function($app) {
            $namespace = $app['np.theme.manager']->getActiveTheme()->getNamespace();
            return $app['cache_dir'].'/themes/'.$namespace;
        });

        $app['np.theme.asset_file_cache'] = $app->share(function($app) {
            return new Assetic\Cache\FilesystemCache($app['np.theme.asset_cache_dir']);
        });

        $app['np.theme.asset_cache.lifetime'] = 86400; // 24 hours in seconds

        $app['np.theme.asset_cache'] = $app->share(function($app) {
            return new Assetic\Cache\ExpiringCache(
                $app['np.theme.asset_file_cache'],
                $app['np.theme.asset_cache.lifetime']
            );
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
                $app['twig.loader.filesystem']->addPath($theme->getDir(), $theme->getNamespace());
            }

            $app['np.theme.manager']->activateTheme($app['np.theme.active']);

            if (!empty($app['np.theme.custom_settings'])) {
                $app['np.theme.manager']->getActiveTheme()->customize($app['np.theme.custom_settings']);
            }

            $app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
                $twig->addGlobal('themes_path', '/themes/');
                $twig->addGlobal('active_theme', $app['np.theme.active']);
                $twig->addGlobal('standalone', true); // TODO: this will determine if we're in the larger NP app
                $twig->addExtension(new ThemeTwigExtension(
                    $app['np.theme.manager'],
                    $app['np.theme.templates.custom_css'],
                    $app['np.theme.minify_assets']
                ));

                return $twig;
            }));

            $app['twig.loader.filesystem']->addPath(__DIR__.'/../Resources/views', 'theme_admin');
        });

        $app->finish(function(Request $request, Response $response) use ($app) {
            if ($app['np.theme.minify_assets']) {
                # Recompile assets if their cache is out of date
                # Run from finish callback so it doesn't slow down the request,
                # but might be better as a cron job
                $assetHelper = new AssetHelper(
                    $app['np.theme.manager']->getActiveTheme(),
                    $app['np.theme.asset_cache_dir'],
                    $app['monolog']
                );
                $assetHelper->validateCaches();
            }
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

        $app->get('/themes/{theme}'.AssetHelper::PATH_MIN_CSS, 'np.theme.controller:minifyStylesheetsAction')
            ->convert('theme', $themeProvider)
            ->bind('get_minified_stylesheets');

        $app->get('/themes/{theme}'.AssetHelper::PATH_MIN_JS, 'np.theme.controller:minifyJavascriptsAction')
            ->convert('theme', $themeProvider)
            ->bind('get_minified_javascripts');

        $app->mount($app['np.theme.mount_point'], new ThemeControllerProvider());
    }
}
