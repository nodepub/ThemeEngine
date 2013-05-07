<?php

namespace NodePub\ThemeEngine;

final class ThemeEvents
{
    /**
     * The np.theme.activate event is thrown each time a theme is activated.
     *
     * The event listener receives an
     * NodePub\ThemeEngine\Event\ThemeActivateEvent instance.
     *
     * @var string
     */
    const THEME_ACTIVATE = 'np.theme.activate';
}