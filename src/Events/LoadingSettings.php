<?php

namespace Spatie\LaravelSettings\Events;

use Illuminate\Support\Collection;

class LoadingSettings
{
    /** @var string */
    public $settingsClass;

    /** @var Collection */
    public $properties;

    public function __construct(string $settingsClass, Collection $properties)
    {
        $this->settingsClass = $settingsClass;
        $this->properties = $properties;
    }
}
