NodePub Theme Engine
====================

A simple web site theme engine built with Twig.

Each theme has a namespace, and themes can inherit templates from one another.

Includes a Silex Service Provider.

## Defining a Theme

A theme is a collection of twig templates, css, js, and images. Each theme has a config.yml file that contains metadata about the theme, and defines the customizable settings of the theme.

config.yml
* css
* js
* images
layout.twig

## Twig Functions

**theme_styles**: renders the style tags for the current theme's CSS. By default, the themes's configured CSS will be minified into a single file. This can be turned off so that it renders a separate script tag for each configured JavaScript file.

**theme_javascripts**: renders the script tags for the current theme's JavaScript files. By default, the theme's configured JavaScript files will be minified into a single file. This can be turned off so that it renders a separate script tag for each configured JavaScript file.

**theme_icons**: renders favicon and apple touch icon meta tags if they are defined in the current theme

All twig templates also have access to a site object.

site.title
site.name
site.tagline
site.description

## Asset Minification

CSS and JavaScript files are minified by default, so that each theme will have only one CSS file and one JavaScript file. Minification can be turned off by setting $app['np.theme.minify_assets'] to false.

## Theme Customization

Most themes have customizable settings for changing the look and style of the theme. These settings are defined in a theme's config.yml file

Types of settings:

* fonts
* colors
* background colors
* background images

## Theme Switching