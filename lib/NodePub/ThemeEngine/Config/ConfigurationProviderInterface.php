<?php

namespace NodePub\ThemeEngine\Config;

interface ConfigurationProviderInterface
{
    public function get($themeName);

    public function update($themeName, array $themeSettings);
}