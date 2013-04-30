<?php

namespace NodePub\ThemeEngine\Config;

interface ConfigurationProviderInterface
{
    public function get($themeName);

    /**
     * @return bool
     */
    public function update($themeName, array $themeSettings);
}