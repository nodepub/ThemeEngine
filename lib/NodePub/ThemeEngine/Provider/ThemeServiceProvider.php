<?php

namespace NodePub\ThemeEngine\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use NodePub\ThemeEngine\ThemeManager;

/**
 * Service Provider for Silex integration
 */
class ThemeServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['theme.global_areas'] = $app->share(function($app) {
            return new ArrayCollection();
        });

        $app['theme.areas'] = $app->share(function($app) {
            return new ArrayCollection();
        });
        
        $app['theme.manager'] = $app->share(function($app) {
            return new ThemeManager($app['theme.paths']);
        });

        $app['theme.templates.ext'] = 'twig';
        $app['theme.templates.custom_css'] = '_styles.css.twig';
    }

    public function boot(Application $app)
    {
        $app->before(function() use ($app) {
            $app['theme.manager']->setTemplateFileExtension($app['theme.templates.ext']);
            $app['theme.manager']->initialize();

            // iterate over loaded themes and add their path and namespace to Twig
            $themes = $app['theme.manager']->loadThemes();
            foreach ($themes as $theme) {
                $app['twig.loader.filesystem']->addPath($theme->getPath(), $theme->getNamespace());
            }

            $app['theme.manager']->setCurrentTheme($app['theme.current']);
            $app['theme.manager']->getCurrentTheme()->customize($app['theme.custom_settings']);
        });
    }
}
