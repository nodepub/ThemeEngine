<?php

namespace NodePub\ThemeEngine;

use Symfony\Component\HttpFoundation\ParameterBag;
use NodePub\ThemeEngine\Model\Asset;

class Theme
{
    /**
     * @var ParameterBag
     */
    protected $config;

    /**
     * @var ParameterBag
     */
    protected $assets;

    /**
     * @var Theme
     */
    protected $parent;

    function __construct(array $config)
    {
        $this->config = new ParameterBag($config);
        $this->assets = new ParameterBag();

        // Cache the default settings so that if the theme is customized,
        // we still have the original values
        $this->config->set('defaultSettings', array_merge(array(), $this->config->get('settings')));
    }

    public function getName()
    {
        return $this->config->get('name');
    }

    public function getNamespace()
    {
        return $this->config->get('namespace', basename($this->getDir()));
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(Theme $parent)
    {
          $this->parent = $parent;
          return $this;
    }

    public function hasParent()
    {
        return $this->parent instanceof Theme;
    }

    public function getParentNamespace()
    {
          return $this->config->get('parent_theme');
    }

    /**
     * Returns the filesystem path of the theme directory
     */
    public function getDir()
    {
        return $this->config->get('dir');
    }

    /**
     * @TODO: implement this check
     */
    public function versionIsOk()
    {
        return true;
    }

    # ===================================================== #
    #    SETTINGS                                           #
    # ===================================================== #

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->config->get('settings', array());
    }

    /**
     * @return array
     */
    public function getDefaultSettings()
    {
        return $this->config->get('defaultSettings');
    }

    /**
     * @return array
     */
    public function getSettingsKeys()
    {
        return array_keys($this->getSettings());
    }

    /**
     * Merges an array of custom settings with this theme's settings
     * @return null
     */
    public function customize(array $settings)
    {
        $this->config->set('settings', array_merge($this->getSettings(), $settings));
    }

    /**
     * Returns only the settings that differ from the default
     * @return array
     */
    public function getCustomSettings($settings = array())
    {
        $settings = !empty($settings) ? $settings : $this->getSettings();
        $defaultSettings = $this->getDefaultSettings();
        $customSettings = array();

        foreach ($settings as $key => $value) {
            if (isset($defaultSettings[$key]) && $defaultSettings[$key] !== $value) {
                $customSettings[$key] = $value;
            }
        }

        return $customSettings;
    }

    # ===================================================== #
    #    STYLESHEETS                                        #
    # ===================================================== #

    /**
     * @return array
     */
    public function getStylesheets()
    {
        return $this->config->get('stylesheets', array());
    }

    /**
     * Returns an array of the Theme's Stylesheet file paths,
     * merged with those its Parent, if one exists.
     * @return array
     */
    public function getStylesheetPaths()
    {
        $stylesheets = array_map(array($this, 'expandAssetPath'), $this->getStylesheets());

        // prepend parent's styles
        if ($this->hasParent()) {
            $stylesheets = array_merge($this->parent->getStylesheetPaths(), $stylesheets);
        }

        return $stylesheets;
    }

    /**
     * Returns an array of the Themes's Stylesheet Asset objects,
     * merged with those its Parent, if one exists.
     */
    public function getStylesheetAssets()
    {
        // defer the asset building until it's needed
        if (is_null($stylesheets = $this->assets->get('stylesheets'))) {
            $assets = $this->buildAssets();
            $stylesheets = $assets['stylesheets'];
        }

        // prepend parent's styles
        if ($this->hasParent()) {
            $stylesheets = array_merge($this->parent->getStylesheetAssets(), $stylesheets);
        }

        return $stylesheets;
    }

    # ===================================================== #
    #    JAVASCRIPTS                                        #
    # ===================================================== #

    /**
     * @return array
     */
    public function getJavaScripts()
    {
        return $this->config->get('javascripts', array());
    }

    /**
     * Returns an array of the Theme's JavaScript file paths,
     * merged with those its Parent, if one exists.
     * @return array
     */
    public function getJavaScriptPaths()
    {
        $javascripts = array_map(array($this, 'expandAssetPath'), $this->getJavaScripts());

        // prepend parent's scripts
        if ($this->hasParent()) {
            $javascripts = array_merge($this->parent->getJavaScriptPaths(), $javascripts);
        }

        return $javascripts;
    }

    /**
     * Returns an array of the Themes's JavaScript Asset objects,
     * merged with those its Parent, if one exists.
     */
    public function getJavaScriptAssets()
    {
        // defer the asset building until it's needed
        if (is_null($javascripts = $this->assets->get('javascripts'))) {
            $assets = $this->buildAssets();
            $javascripts = $assets['javascripts'];
        }

        // prepend parent's scripts
        if ($this->hasParent()) {
            $javascripts = array_merge($this->parent->getJavaScriptAssets(), $javascripts);
        }

        return $javascripts;
    }

    /**
     * Expands a relative file path into a full filesystem path.
     * Paths starting with http: are not altered.
     * @return string
     */
    protected function expandAssetPath($path)
    {
        if (preg_match('/^https?:/', $path)) {
            return $path;
        } elseif (0 === strpos($path, '/')) {
            return realpath($this->getDir().'/..').$path;
        } else {
            $ext = pathinfo($path, PATHINFO_EXTENSION);
            return implode('/', array($this->getDir(), $ext, $path));
        }
    }

    /**
     * Gets a Theme's js and css file paths and
     * creates Asset objects for each of them.
     * Caches the two arrays in the 'assets' property. 
     * @return array
     */
    protected function buildAssets()
    {
        $stylesheets = array_map(function($filePath) {
            return new Asset($filePath);
        }, $this->getStylesheetPaths());

        $javascripts = array_map(function($filePath) {
            return new Asset($filePath);
        }, $this->getJavaScriptPaths());

        $this->assets->set('stylesheets', $stylesheets);
        $this->assets->set('javascripts', $javascripts);

        return array(
            'stylesheets' => $stylesheets,
            'javascripts' => $javascripts
        );
    }
}