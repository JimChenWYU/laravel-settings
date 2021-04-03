<?php

namespace Spatie\LaravelSettings;

use Exception;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelSettings\Factories\SettingsCastFactory;
use Spatie\LaravelSettings\Factories\SettingsRepositoryFactory;
use Spatie\LaravelSettings\SettingsCasts\SettingsCast;
use Spatie\LaravelSettings\SettingsRepositories\SettingsRepository;

class SettingsConfig
{
    /** @var string|\Spatie\LaravelSettings\Settings */
    private $settingsClass;

    /** @var array<string, ?\Spatie\LaravelSettings\SettingsCasts\SettingsCast>|\Illuminate\Support\Collection */
    private $casts;

    /** @var array<string, \ReflectionProperty>|\Illuminate\Support\Collection */
    private $reflectionProperties;

    /** @var string[]|\Illuminate\Support\Collection */
    private $encrypted;

    /** @var string[]|\Illuminate\Support\Collection */
    private $locked;

    /** @var \Spatie\LaravelSettings\SettingsRepositories\SettingsRepository  */
    private $repository;

    public function __construct(string $settingsClass)
    {
        if (! is_subclass_of($settingsClass, Settings::class)) {
            throw new Exception("Tried decorating {$settingsClass} which is not extending `Spatie\LaravelSettings\Settings::class`");
        }

        $this->settingsClass = $settingsClass;

        $this->reflectionProperties = collect(
            (new ReflectionClass($settingsClass))->getProperties(ReflectionProperty::IS_PUBLIC)
        )->mapWithKeys(function (ReflectionProperty $property) {
            return [$property->getName() => $property];
        });

        $this->casts = $this->reflectionProperties
            ->map(function (ReflectionProperty $reflectionProperty) {
                return SettingsCastFactory::resolve(
                    $reflectionProperty,
                    $this->settingsClass::casts()
                );
            });

        $this->encrypted = collect($this->settingsClass::encrypted());

        $this->repository = SettingsRepositoryFactory::create($this->settingsClass::repository());
    }

    public function getName(): string
    {
        return $this->settingsClass;
    }

    public function getReflectedProperties(): Collection
    {
        return $this->reflectionProperties;
    }

    public function getRepository(): SettingsRepository
    {
        return $this->repository;
    }

    public function getGroup(): string
    {
        return $this->settingsClass::group();
    }

    public function isEncrypted(string $name): bool
    {
        return $this->encrypted->contains($name);
    }

    public function isLocked(string $name): bool
    {
        return $this->getLocked()->contains($name);
    }

    public function getCast(string $name): ?SettingsCast
    {
        return $this->casts->get($name);
    }

    public function lock(string ...$names): self
    {
        $this->locked = $this->getLocked()->merge($names);

        $this->repository->lockProperties(
            $this->getGroup(),
            $names
        );

        return $this;
    }

    public function unlock(string ...$names): self
    {
        $this->locked = $this->getLocked()->diff($names);

        $this->repository->unlockProperties(
            $this->getGroup(),
            $names
        );

        return $this;
    }

    public function getLocked(): Collection
    {
        if (! empty($this->locked)) {
            return $this->locked;
        }

        return $this->locked = collect(
            $this->repository->getLockedProperties($this->settingsClass::group())
        );
    }
}
