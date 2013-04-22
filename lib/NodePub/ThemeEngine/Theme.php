<?php

namespace NodePub\ThemeEngine;

use Doctrine\Common\Collections\ArrayCollection;

class Theme
{
    protected $config;

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
    }

    function getName()
    {
        return $this->config->get('name');
    }

    function getNamespace()
    {
        $namespace = $this->config->get('namespace');

        if (is_null($namespace)) {
            $namespace = basename($this->getPath());
        }

        return $namespace;
    }

    function getPath()
    {
        return $this->config->get('path');
    }

    /**
     * @TODO: 
     */
    function versionIsOk()
    {
        return true;
    }

    /**
     * @return array
     */
    function getSettings()
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
    function getSettingsKeys()
    {
        return array_keys($this->getSettings());
    }

    /**
     * @return array
     */
    function getStylesheets()
    {
        $stylesheets = $this->config->get('stylesheets');

        if (is_null($stylesheets)) {
            $stylesheets = array();
        }

        return $stylesheets;
    }

    function customize($settings)
    {
        $this->config->set('settings', array_merge($this->getSettings(), $settings));
    }
}