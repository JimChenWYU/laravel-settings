<?php

namespace Spatie\LaravelSettings\Tests\TestClasses;

use Spatie\LaravelSettings\Settings;

class DummySimpleSettings extends Settings
{
    /** @var string  */
    public $name;
    /** @var string  */
    public $description;

    public static function group(): string
    {
        return 'dummy_simple';
    }
}
