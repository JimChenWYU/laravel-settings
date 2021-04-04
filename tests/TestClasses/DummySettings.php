<?php

namespace Spatie\LaravelSettings\Tests\TestClasses;

use DateTimeImmutable;
use DateTimeZone;
use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\SettingsCasts\DtoCast;

class DummySettings extends Settings
{
    /** @var string */
    public $string;
    /** @var bool */
    public $bool;
    /** @var int */
    public $int;
    /** @var array */
    public $array;
    /** @var ?string */
    public $nullable_string;
    /** @var DummyDto */
    public $dto;

    /** @var \Spatie\LaravelSettings\Tests\TestClasses\DummyDto[] */
    public $dto_array;

    // Todo: enable this later
//    /** @var \Spatie\LaravelSettings\Tests\TestClasses\DummyDto[] */
//    public array $dto_collection;

    /** @var DateTimeImmutable */
    public $date_time;
    /** @var \Carbon\Carbon */
    public $carbon;
    /** @var ?DateTimeZone */
    public $nullable_date_time_zone;

    public static function group(): string
    {
        return 'dummy';
    }

    public static function casts(): array
    {
        return [
            'dto' => new DtoCast(DummyDto::class),
        ];
    }
}
