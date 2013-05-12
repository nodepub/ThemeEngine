<?php

namespace NodePub\ThemeEngine\Model;

class Asset
{
    const EXT_JS = 'js';
    const EXT_CSS = 'css';
    const EXT_SASS = 'sass';

    protected $path;
    
    function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Returns the Asset's filesystem path
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getDir()
    {
        return dirname($this->path);
    }

    /**
     * Returns the web-relative path of the file
     * If the relative path can't be determined,
     * return the full path, which would be the case for http paths
     */
    public function getWebPath()
    {
        $path = strstr($this->path, '/themes');
        return $path ?: $this->path;
    }

    /**
     * Returns the file extension
     */
    public function getExtension()
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Returns the filter type of an asset.
     * An asset with the path /themes/foo/css/styles.css.sass
     * has a filter of 'sass' with the intent of 'css'
     */
    public function getFilterType()
    {
        $segments = explode('.', $this->path);

        if (count($segments) >= 3) {
            return '';
        }

    }
}