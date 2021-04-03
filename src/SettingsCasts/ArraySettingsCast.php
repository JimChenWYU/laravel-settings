<?php

namespace Spatie\LaravelSettings\SettingsCasts;

class ArraySettingsCast implements SettingsCast
{
    /** @var SettingsCast */
    protected $cast;

    public function __construct(SettingsCast $cast)
    {
        $this->cast = $cast;
    }

    public function getCast(): ?SettingsCast
    {
        return $this->cast;
    }

    public function get($payload): array
    {
        return array_map(
            function ($data) {
                return $this->cast->get($data);
            },
            $payload
        );
    }

    public function set($payload)
    {
        return array_map(
            function ($data) {
                return $this->cast->set($data);
            },
            $payload
        );
    }
}
