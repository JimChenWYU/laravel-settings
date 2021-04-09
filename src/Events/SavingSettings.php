<?php

namespace Spatie\LaravelSettings\Events;

use Illuminate\Support\Collection;
use Spatie\LaravelSettings\Settings;

class SavingSettings
{
    /** @var Settings */
    public $settings;

    /** @var Collection */
    public $properties;

    /** @var ?Collection */
    public $originalValues;

    public function __construct(
        Collection $properties,
        ?Collection $originalValues,
        Settings $settings
    ) {
        $this->properties = $properties;

        $this->originalValues = $originalValues;

        $this->settings = $settings;
    }
}
