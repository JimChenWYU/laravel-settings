<?php

namespace Spatie\LaravelSettings\Events;

use Spatie\LaravelSettings\Settings;

class SettingsLoaded
{
    /** @var Settings */
    public $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }
}
