<?php

namespace NodePub\ThemeEngine;

use Symfony\Component\HttpFoundation\ParameterBag;

class Theme
{
    /**
     * @var ParameterBag
     */
    protected $config;

    /**
     * @var Theme
     */
    protected $parent;

    function __construct(array $config)
    {
        $this->config = new ParameterBag($config);

        // Cache the default settings so that if the theme is customized,
        // we can access the original values
        $this->config->set('defaultSettings', array_merge(array(), $this->config->get('settings')));
    }

    public function getName()
    {
        return $this->config->get('name');
    }

    public function getNamespace()
    {
        return $this->config->get('namespace', basename($this->getPath()));
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

    public function getPath()
    {
        return $this->config->get('path');
    }

    /**
     * @TODO: implement this check
     */
    public function versionIsOk()
    {
        return true;
    }

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
     * @return array
     */
    public function getStylesheets()
    {
        return $this->config->get('stylesheets', array());
    }

    /**
     * Returns an array of the Theme's stylesheets,
     * merged with any Parent Theme's stylesheets.
     * Expands the paths of each.
     * @return array
     */
    public function getStylesheetPaths()
    {
        $stylesheets = array_map(array($this, 'getAssetPath'), $this->getStylesheets());

        // prepend parent's styles
        if ($this->hasParent()) {
            $stylesheets = array_merge($this->parent->getStylesheetPaths(), $stylesheets);
        }

        return $stylesheets;
    }

    /**
     * @return array
     */
    public function getJavaScripts()
    {
        return $this->config->get('javascripts', array());
    }

    /**
     * Returns an array of the Theme's javascripts,
     * merged with any Parent Theme's javascripts.
     * Expands the paths of each.
     * @return array
     */
    public function getJavaScriptPaths()
    {
        $scripts = array_map(array($this, 'getAssetPath'), $this->getJavaScripts());

        // prepend parent's scripts
        if ($this->hasParent()) {
            $scripts = array_merge($this->parent->getJavaScriptPaths(), $scripts);
        }

        return $scripts;
    }

    /**
     * Builds the relative path of an asset
     * If the asset is already a full or root-relative path,
     * it is not altered.
     */
    protected function getAssetPath($asset)
    {
        if (preg_match('/^https?:|\//', $asset)) {
            return $asset;
        } else {
            $ext = pathinfo($asset, PATHINFO_EXTENSION);
            return sprintf('%s/%s/%s', $this->getNamespace(), $ext, $asset);
        }
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
}