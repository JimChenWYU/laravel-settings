<?php

namespace Spatie\LaravelSettings\Events;

use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;

class SavingSettings
{
    /** @var Settings  */
    public $settings;

    /** @var Collection  */
    public $properties;

    public function __construct(
        Collection $properties,
        Settings $settings
    ) {
        $this->properties = $properties;

        $this->settings = $settings;
    }
}
