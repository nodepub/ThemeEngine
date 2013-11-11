<?php

namespace NodePub\ThemeEngine\Event;

use Symfony\Component\EventDispatcher\Event;
use NodePub\ThemeEngine\ThemeManager;

class ThemeManagerInitEvent extends Event
{
    protected $themeManager;

    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    public function getThemeManager()
    {
        return $this->themeManager;
    }
}