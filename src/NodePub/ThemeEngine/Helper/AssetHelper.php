<?php

namespace NodePub\ThemeEngine\Helper;

use NodePub\ThemeEngine\Theme;
use NodePub\ThemeEngine\Model\Asset as NpAsset;
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Filter\Yui\JsCompressorFilter;
use Assetic\Filter\Yui\CssCompressorFilter;
use Assetic\Filter\SassFilter;

class AssetHelper
{
    const HASH_ALGORITHM = 'sha1';
    const PATH_MIN_JS = '/js/scripts.min.js';
    const PATH_MIN_CSS = '/css/styles.min.css';

    protected $theme,
              $jarPath;

    function __construct(Theme $theme, $cacheDir)
    {
        $this->theme = $theme;
        $this->cacheDir = $cacheDir;
        $this->jarPath = __DIR__.'/../../../../bin/yuicompressor.jar';
    }

    public function getCssMinPath()
    {
        return $this->theme->getDir() . self::PATH_MIN_CSS;
    }

    public function getJsMinPath()
    {
        return $this->theme->getDir() . self::PATH_MIN_JS;
    }

    public function compileStylesheets()
    {
        return $this->compile($this->theme->getStylesheetAssets(), $this->getCssMinPath());
    }

    public function compileJavascripts()
    {
        return $this->compile($this->theme->getJavaScriptAssets(), $this->getJsMinPath());
    }

    /**
     * Compiles an array of assets and returns them as a single string.
     * Saves the compilation to a file
     * @return string
     */
    public function compile(array $assets, $cachePath)
    {
        $collection = $this->getAsseticAssetCollection($assets);
        $compilation = $collection->dump();

        if ($cachePath) {
            $this->cacheCompilation($cachePath, $compilation);
        }

        return $compilation;
    }

    public function cacheCompilation($cachePath, $compilation)
    {
        static::write($cachePath, $compilation);
    }

    public function validateCaches()
    {
        $cacheParams = array(
            array(
                '_css_cache',
                $this->getAsseticAssetCollection($this->theme->getStylesheetAssets()),
                array($this, 'compileStylesheets')
            ),
            array(
                '_js_cache',
                $this->getAsseticAssetCollection($this->theme->getJavaScriptAssets()),
                array($this, 'compileJavaScripts')
            )
        );

        foreach ($cacheParams as $params) {
            call_user_func_array(array($this, 'validateCache'), $params);
        }

        return true;
    }

    public function validateCache($cacheFile, $assetCollection, $compileCallback)
    {
        $hash = hash_init(self::HASH_ALGORITHM);

        foreach ($assetCollection as $asset) {
            hash_update($hash, $asset->getLastModified());
        }

        $hash = hash_final($hash);

        $cachePath = $this->cacheDir.$cacheFile;

        if (!file_exists($cachePath)) {
            touch($cachePath);
        }

        if (0 !== strcmp($hash, file_get_contents($cachePath))) {
            // recompile the assets
            call_user_func($compileCallback);

            // update the cache
            file_put_contents($cachePath, $hash);
        }
    }

    /**
     * @return AssetCollection
     */
    protected function getAsseticAssetCollection(array $npAssets)
    {
        $collection = new AssetCollection();

        foreach ($npAssets as $asset) {
            try {
                $collection->add($this->getAsseticAsset($asset));
            } catch (\Exception $e) {
                // TODO log the error, it's not critical
            }
        }

        return $collection;
    }

    /**
     * Creates an Assetc-compatible object
     * @return FileAsset|HttpAsset
     */
    protected function getAsseticAsset(NpAsset $asset)
    {
        $class = 'Assetic\Asset\FileAsset';

        if (0 === strpos($asset->getPath(), 'http')) {
            $class = 'Assetic\Asset\HttpAsset';
        }

        return new $class($asset->getPath(), $this->getFilters($asset));
    }

    /**
     * Create the asset filters depending on asset file extension
     */
    protected function getFilters(NpAsset $asset)
    {
        $filters = array();
        $ext = $asset->getExtension();

        switch ($ext) {
            case NpAsset::EXT_JS:
                $filters[]= new JsCompressorFilter($this->jarPath);
                break;

            case NpAsset::EXT_CSS:
                $filters[]= new CssCompressorFilter($this->jarPath);
                break;

            case NpAsset::EXT_SASS:
                $filters[]= new SassFilter();
                break;
        }

        return $filters;
    }

    protected static function write($path, $contents)
    {
        if (!is_dir($dir = dirname($path)) && false === @mkdir($dir, 0777, true)) {
            throw new \RuntimeException('Unable to create directory '.$dir);
        }

        if (false === @file_put_contents($path, $contents)) {
            throw new \RuntimeException('Unable to write file '.$path);
        }
    }
}