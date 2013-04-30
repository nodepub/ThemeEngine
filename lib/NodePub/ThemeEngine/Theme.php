<?php

namespace NodePub\ThemeEngine;

use Symfony\Component\HttpFoundation\ParameterBag;

class Theme
{
    /**
     * @var ArrayCollection
     */
    protected $config;

    /**
     * @var Theme
     */
    protected $parent;

    function __construct(array $config)
    {
        // Make sure we always have these values
        $defaults = array(
            'name'      => '',
            'path'      => '',
            'settings'  => array(),
            'stylesheets'  => array(),
        );

        $this->config = new ParameterBag(array_merge($defaults, $config));

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
        $stylesheets = $this->config->get('stylesheets', array());
        $namespace = $this->getNamespace();
        $stylesheets = array_map(function($stylesheet) use ($namespace) {
            return $namespace . '/css/' . $stylesheet;
        }, $stylesheets);

        // prepend parent's styles
        if ($this->parent instanceof Theme) {
            $stylesheets = array_merge($this->parent->getStylesheets(), $stylesheets);
        }

        return $stylesheets;

        //return $this->getArrayMergedWithParent('stylesheets');
    }

    /**
     * @return array
     */
    public function getArrayMergedWithParent($key)
    {
        $values = $this->config->get($key, array());

        // prepend parent's values
        if ($this->hasParent()) {
            $values = array_merge($this->parent->getArrayMergedWithParent($key), $values);
        }

        return $values;
    }

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