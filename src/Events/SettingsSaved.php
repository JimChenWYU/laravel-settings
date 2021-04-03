<?php

namespace Spatie\LaravelSettings\Events;

use Spatie\LaravelSettings\Settings;

class SettingsSaved
{
    /** @var Settings  */
    public $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }
}
