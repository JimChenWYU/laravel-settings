<?php

namespace Spatie\LaravelSettings;

use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use ReflectionProperty;
use Serializable;
use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Events\SettingsLoaded;
use Spatie\LaravelSettings\Events\SettingsSaved;

abstract class Settings implements Arrayable, Jsonable, Responsable, Serializable
{
    /** @var \Spatie\LaravelSettings\SettingsMapper */
    private $mapper;

    /** @var \Spatie\LaravelSettings\SettingsConfig */
    private $config;

    /** @var bool */
    private $loaded = false;

    /** @var bool */
    private $configInitialized = false;

    /** @var ?Collection */
    protected $originalValues = null;

    abstract public static function group(): string;

    public static function repository(): ?string
    {
        return null;
    }

    public static function casts(): array
    {
        return [];
    }

    public static function encrypted(): array
    {
        return [];
    }

    /**
     * @param array $values
     *
     * @return static
     */
    public static function fake(array $values): self
    {
        $settingsMapper = app(SettingsMapper::class);

        $propertiesToLoad = $settingsMapper->initialize(static::class)
            ->getReflectedProperties()
            ->keys()
            ->reject(function (string $name) use ($values) {
                return array_key_exists($name, $values);
            });

        $mergedValues = $settingsMapper
            ->fetchProperties(static::class, $propertiesToLoad)
            ->merge($values)
            ->toArray();

        return app(Container::class)->instance(static::class, new static(
            $settingsMapper,
            $mergedValues
        ));
    }

    public function __construct(SettingsMapper $mapper, array $values = [])
    {
        $this->loadConfig($mapper);

        foreach ($this->config->getReflectedProperties()->keys() as $name) {
            unset($this->{$name});
        }

        if (! empty($values)) {
            $this->loadValues($values);
        }
    }

    public function __get($name)
    {
        $this->loadValues();

        return $this->{$name};
    }

    public function __set($name, $value)
    {
        $this->loadValues();

        $this->{$name} = $value;
    }

    public function __debugInfo()
    {
        $this->loadValues();
    }

    /**
     * @param \Illuminate\Support\Collection|array $properties
     *
     * @return $this
     */
    public function fill($properties): self
    {
        foreach ($properties as $name => $payload) {
            $this->{$name} = $payload;
        }

        return $this;
    }

    public function save(): self
    {
        $properties = $this->toCollection();

        event(new SavingSettings($properties, $this->originalValues, $this));

        $values = $this->mapper->save(static::class, $properties);
        $this->fill($values);
        $this->originalValues = $values;

        event(new SettingsSaved($this));

        return $this;
    }

    public function lock(string ...$properties)
    {
        $this->loadConfig();

        $this->config->lock(...$properties);
    }

    public function unlock(string ...$properties)
    {
        $this->loadConfig();

        $this->config->unlock(...$properties);
    }

    public function getLockedProperties(): array
    {
        $this->loadConfig();

        return $this->config->getLocked()->toArray();
    }

    public function toCollection(): Collection
    {
        $this->loadConfig();

        return $this->config
            ->getReflectedProperties()
            ->mapWithKeys(function (ReflectionProperty $property) {
                return [
                    $property->getName() => $this->{$property->getName()},
                ];
            });
    }

    public function toArray(): array
    {
        return $this->toCollection()->toArray();
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function toResponse($request)
    {
        return response()->json($this->toJson());
    }

    public function serialize(): string
    {
        return serialize($this->toArray());
    }

    public function unserialize($serialized): void
    {
        $properties = unserialize($serialized);

        $this->fill($properties);
        $this->originalValues = collect($properties);
        $this->loaded = true;
    }

    private function loadValues(?array $values = null): self
    {
        if ($this->loaded) {
            return $this;
        }

        if (! isset($values)) {
            $values = $this->mapper->load(static::class);
        }

        $this->loaded = true;
        $this->fill($values);
        $this->originalValues = collect($values);

        event(new SettingsLoaded($this));

        return $this;
    }

    private function loadConfig(?SettingsMapper $mapper = null): self
    {
        if ($this->configInitialized) {
            return $this;
        }

        $this->mapper = $mapper ?? app(SettingsMapper::class);
        $this->config = $this->mapper->initialize(static::class);
        $this->configInitialized = true;

        return $this;
    }
}
