<?php

namespace NodePub\ThemeEngine;

use Doctrine\Common\Collections\ArrayCollection;
use NodePub\Common\Trait\SourceDirectoryAwareInterface;
use NodePub\Common\Trait\SourceDirectoryAwareTrait;
use NodePub\ThemeEngine\Event\ThemeActivateEvent;
use NodePub\ThemeEngine\Event\ThemeManagerInitEvent;
use NodePub\ThemeEngine\Theme;
use NodePub\ThemeEngine\ThemeEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class ThemeManager implements SourceDirectoryAwareInterface
{
    use SourceDirectoryAwareTrait;
    
    protected $initialized,
              $activeTheme,
              $activeThemes,
              $templateFileExtension,
              $eventDispatcher;

    /**
     * Caches raw theme config settings loaded from YAML files
     * Only used as a cache, use $themes when accessing the properties
     */
    protected $themes;

    public function __construct($themePaths, EventDispatcherInterface $dispatcher)
    {
        $this->initialized = false;
        $this->activeThemes = new ArrayCollection();
        $this->templateFileExtension = 'twig';
        $this->eventDispatcher = $dispatcher;

        if (is_array($themePaths)) {
            foreach ($themePaths as $dir) {
                $this->addSource($dir);
            }
        } elseif (is_string($themePaths)) {
            $this->addSource($themePaths);
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
        if (is_string($themeName)) {
            $theme = $this->getTheme($themeName);
        }

        if ($theme) {
            $this->activeTheme = $theme;
            $this->eventDispatcher->dispatch(ThemeEvents::THEME_ACTIVATE, new ThemeActivateEvent($theme));

            return $theme;
        } else {
            $warning = $this->initialized ? '' : ' ThemeManager not initialized yet.';

            throw new \Exception(sprintf('No active themes match the name "%s"'.$warning, $themeName));
        }
    }

    /**
     * @return Theme
     */
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
        $finder = Finder::create()
            ->followLinks()
            ->depth('== 0');
            ;
        
        return $this->findInSourceDirs($finder);
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
            $themeConfig['dir'] = $file->getPath();

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
        $finder = Finder::create()
            ->files()
            ->followLinks()
            ->name('config.yml')
            ;
        
        return $this->findInSourceDirs($finder);
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

        $this->eventDispatcher->dispatch(ThemeEvents::THEME_MANAGER_INITIALIZED, new ThemeManagerInitEvent($this));

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
                ->in($theme->getDir())
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

    public function setTheme(Theme $theme)
    {
        return $this->activeThemes->set($theme->getNamespace(), $theme);
    }

    /**
     * All themes have to be loaded before we can set parent relationships
     */
    public function setThemeParents()
    {
        foreach ($this->activeThemes as $theme) {
            if ($parentNamespace = $theme->config->get('parent_theme')) {
                $parentTheme = $this->getTheme($parentNamespace);
                $theme->setParent($parentTheme);
            }
        }
    }

    public function getActiveThemeNames()
    {
        $themes = $this->loadThemes();
        $themeNames = array();

        foreach ($themes as $theme) {
            $themeNames[$theme->getNamespace()] = $theme->getName();
        }

        return $themeNames;
    }
}
