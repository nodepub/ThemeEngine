<?php

namespace NodePub\ThemeEngine\Event;

use Symfony\Component\EventDispatcher\Event;
use NodePub\ThemeEngine\Theme;

class ThemeActivateEvent extends Event
{
    protected $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }

    public function getTheme()
    {
        return $this->theme;
    }
}