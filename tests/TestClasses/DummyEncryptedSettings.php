<?php

namespace Spatie\LaravelSettings\Tests\TestClasses;

use DateTime;
use Spatie\LaravelSettings\Settings;

class DummyEncryptedSettings extends Settings
{
    /** @var string */
    public $string;

    /** @var string|null */
    public $nullable;

    /** @var DateTime */
    public $cast;

    public static function group(): string
    {
        return 'dummy_encrypted';
    }

    public static function encrypted(): array
    {
        return ['string', 'nullable', 'cast'];
    }
}
