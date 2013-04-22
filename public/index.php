<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';
$loader->add('NodePub', __DIR__.'/../lib');

ini_set('display_errors', 1);
error_reporting(E_ALL);

$app = new Silex\Application();

# ===================================================== #
#    ADDITIONAL SERVICE PROVIDERS                       #
# ===================================================== #

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

# ===================================================== #
#    THEME CONFIG                                       #
# ===================================================== #

// This is temporary, need a way to abstract customized theme settings
// so that they could be coming from anywhere - db, static files, etc.
// We shouldn't care where they are coming from at this point,
// but we may want to create a default way to add custom settings
$app['theme.settings'] = $app->share(function($app) {
    return array(
        'default' => array(
            'header_bg_color' => '#1D96D7'
        )
    );
});

$app['theme.paths'] = realpath(__DIR__.'/themes');
$app['theme.current'] = 'default';
$app['theme.custom_settings'] = $app['theme.settings'][$app['theme.current']];

$app->register(new NodePub\ThemeEngine\Provider\ThemeServiceProvider());

# ===================================================== #
#    TWIG CONFIG                                        #
# ===================================================== #

$app['twig_extension'] = $app->share(function($app) {
    $extension = new NodePub\ThemeEngine\TwigExtension();
    $extension->themeManager = $app['theme.manager'];
    $extension->customCssTemplateName = $app['theme.templates.custom_css'];

    return $extension;
});

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.options'    => array(
        'autoescape' => false
    )
));

$app['twig.loader.filesystem']->addPath(__DIR__.'/../templates', 'app');

// TODO: define this dynamically
$app['twig']->addGlobal('theme_path', '/themes/'.$app['theme.current'].'/');
$app['twig']->addGlobal('site', array(
    'name'        => 'NodePub',
    'tagline'     => 'I Like Turtles',
    'title'       => 'NodePub Theme Engine',
    'description' => 'This is a dummy site for showing NodePub themes',
    'url'         => 'http://nodepub.com'
));
$app['twig']->addExtension($app['twig_extension']);

# ===================================================== #
#    ROUTES                                             #
# ===================================================== #   

$app->get('/', 'NodePub\ThemeEngine\Controller\ThemeController::themesAction')
    ->bind('get_themes');

$app->get('/{theme}/settings', 'NodePub\ThemeEngine\Controller\ThemeController::settingsAction')
    ->bind('get_theme_settings');

$app->post('/{theme}/settings', 'NodePub\ThemeEngine\Controller\ThemeController::postSettingsAction')
    ->bind('post_theme_settings');

$app->get('/{theme}/{layout}', 'NodePub\ThemeEngine\Controller\ThemeController::previewAction')
    ->bind('get_theme_preview');

$app->get('/rss', function() {
    // just a placeholder for the named route
})->bind('rss');

$app->error(function (\Exception $e, $code) use ($app) {
    echo($e->getMessage());
});

$app->run();