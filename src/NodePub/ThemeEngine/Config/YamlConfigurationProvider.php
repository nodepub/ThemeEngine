<?php

namespace NodePub\ThemeEngine\Config;

use Symfony\Component\Yaml\Yaml;
use NodePub\ThemeEngine\Config\ConfigurationProviderInterface;

/**
 * Loads and saves custom theme settings to a yaml file
 */
class YamlConfigurationProvider implements ConfigurationProviderInterface
{
    protected $filePath,
              $config,
              $defaultConfig;

    /**
     * @param string $filePath
     */
    function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function get($themeName)
    {
        if (is_null($this->config)) {
            $this->load();
        }

        return isset($this->config[$themeName]) ? $this->config[$themeName] : array();
    }

    public function update($themeName, array $themeSettings)
    {
        $this->config[$themeName] = $themeSettings;
        
        try {
            $this->save();
            return true;
        } catch (\Exception $e) {
            # TODO: log the error
            return false;
        }
    }

    /**
     * Loads the yaml config file
     */
    protected function load()
    {
        $this->config = array();

        if (file_exists($this->filePath)) {
            $this->config = Yaml::parse($this->filePath);
        }
    }

    /**
     * Saves the current configuration array to the configuration yaml file
     */
    protected function save()
    {
        $yaml = Yaml::dump($this->config, 2);
        file_put_contents($this->filePath, $yaml);
    }

    protected function getUniqueSettings()
    {
        array_diff($this->config, array2);
    }
}