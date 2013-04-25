<?php

namespace NodePub\ThemeEngine;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;
use Doctrine\Common\Collections\ArrayCollection;
use NodePub\ThemeEngine\Theme;

class ThemeManager
{
    protected $initialized,
              $activeTheme,
              $activeThemes,
              $sourceDirs,
              $templateFileExtension;

    /**
     * Caches raw theme config settings loaded from YAML files
     * Only used as a cache, use $themes when accessing the properties
     */
    protected $themes;

    public function __construct($themePaths)
    {
        $this->initialized = false;
        $this->sourceDirs = array();
        $this->activeThemes = new ArrayCollection();
        $this->templateFileExtension = 'twig';

        if (is_array($themePaths)) {
            foreach ($themePaths as $dir) {
                $this->addSource($dir);
            }
        } elseif (is_string($themePaths)) {
            $this->addSource($themePaths);
        }
    }

    /**
     * Adds a directory to the array of sources that will
     * be searched for themes to load.
     */
    public function addSource($sourcePath)
    {
        if (is_link($sourcePath)) {
            $this->addSource(realpath($sourcePath));
            return;
        }
        
        if (is_dir($sourcePath)) {
            $this->sourceDirs[] = $sourcePath;
        } else {
            throw new \Exception(sprintf('Theme path "%s" is not a readable directory', $sourcePath));
        }
    }

    /**
     * Sets the file extension used for finding theme templates
     */
    public function setTemplateFileExtension($ext)
    {
        $this->templateFileExtension = $ext;
    }

    /**
     * Sets the theme name of the current active theme
     */
    public function activateTheme($themeName)
    {
        if ($theme = $this->getTheme($themeName)) {
            $this->activeTheme = $theme;
            return $theme;
        } else {
            $warning = $this->initialized ? '' : ' ThemeManager not initialized yet.';

            throw new \Exception(sprintf('No active themes match the name "%s"'.$warning, $themeName));
        }
    }

    public function getActiveTheme()
    {
        return $this->activeTheme;
    }
    
    /**
     * Creates a valid namespace from a theme name
     */
    protected function normalizeName($name)
    {
        return strtolower(str_replace(' ', '', $name));
    }

    /**
     * Finds all theme directories
     */
    public function findThemeDirectories()
    {
        $themeDirs = Finder::create()
            ->directories()
            ->depth('== 0');
            ;
        
        foreach ($this->sourceDirs as $dir) {
           $themeDirs->in($dir);
        }

        return $themeDirs;
    }

    /**
     * Returns an array of meta data for all available themes
     * @return array
     */
    public function loadThemes()
    {
        if (is_null($this->themes)) {
            $this->themes = array();
            $configFiles = $this->findConfigFiles();

            foreach ($configFiles as $file) {
                $this->themes[] = $this->loadTheme($file);
            }
        }

        return $this->themes;
    }
    
    /**
     * Loads a YAML config file
     * @return Theme
     */
    protected function loadTheme($file)
    {
        if ($file->isReadable()) {

            $themeConfig = Yaml::parse($file->getPathname());
            
            // Add the directory path as part of the config
            $themeConfig['path'] = $file->getPath();

            $theme = new Theme($themeConfig);

            return $theme;
        }
    }
    
    /**
     * Finds all config.yml files in all defined theme directories
     * @return Finder
     */
    protected function findConfigFiles()
    {
        $configFiles = Finder::create()
            ->files()
            ->name('config.yml')
            ;
        
        foreach ($this->sourceDirs as $dir) {
            $configFiles->in($dir);
        }

        return $configFiles;
    }
    
    /**
     * Initializes valid themes
     */
    public function initialize()
    {
        $themes = $this->loadThemes();

        foreach ($themes as $theme) {
            if (true === $theme->versionIsOk()) {
                $this->activeThemes->set($theme->getNamespace(), $theme);
            }
        }

        $this->initialized = true;
    }
    
    /**
     * Returns array of file names for layouts in a theme.
     * Any file with the specified extension and not prefixed with '_' is considered a layout
     */
    public function getThemeLayouts($themeName)
    {
        $themeName = $this->normalizeName($themeName);
        $layouts = array();
        
        if ($theme = $this->activeThemes->get($themeName)) {
            $layoutFiles = Finder::create()
                ->files()
                ->name('*.'.$this->templateFileExtension)
                ->notName('/^_/')
                ->in($theme->getPath())
                ;
            
            $layouts = $this->getFilenames($layoutFiles);
        }
        
        return $layouts;
    }
    
    /**
     * Returns array of file names from a Finder instance
     * @return array
     */
    protected function getFilenames(Finder $finder)
    {
        $filenames = array();
        
        foreach ($finder as $file) {
            $filenames[] = $file->getFilename();
        }
        
        return $filenames;
    }

    /**
     * @return mixed Theme or null
     */
    public function getTheme($themeName)
    {
        return $this->activeThemes->get($this->normalizeName($themeName));
    }
}
