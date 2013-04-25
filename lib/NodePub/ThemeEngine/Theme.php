<?php

namespace NodePub\ThemeEngine;

use Doctrine\Common\Collections\ArrayCollection;

class Theme
{
    protected $config,
              $defaultSettings;

    function __construct(array $config)
    {
        // Make sure we always have these values
        $defaults = array(
            'name'      => '',
            'path'      => '',
            'settings'  => array(),
            'stylesheets'  => array(),
        );

        $this->config = new ArrayCollection(array_merge($defaults, $config));
        $this->defaultSettings = $config['settings'];
    }

    public function getName()
    {
        return $this->config->get('name');
    }

    public function getNamespace()
    {
        $namespace = $this->config->get('namespace');

        if (is_null($namespace)) {
            $namespace = basename($this->getPath());
        }

        return $namespace;
    }

    public function getPath()
    {
        return $this->config->get('path');
    }

    /**
     * @TODO: 
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
        $settings = $this->config->get('settings');

        if (is_null($settings)) {
            $settings = array();
        }

        return $settings;
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
        $stylesheets = $this->config->get('stylesheets');

        if (is_null($stylesheets)) {
            $stylesheets = array();
        }

        return $stylesheets;
    }

    public function customize(array $settings)
    {
        $this->config->set('settings', array_merge($this->getSettings(), $settings));
    }
}