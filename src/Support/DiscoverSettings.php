<?php

namespace Spatie\LaravelSettings\Support;

use Illuminate\Support\Str;
use Spatie\LaravelSettings\Settings;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Throwable;

class DiscoverSettings
{
    /** @var array */
    protected $directories = [];

    /** @var string */
    protected $basePath = '';

    /** @var string */
    protected $rootNamespace = '';

    /** @var array */
    protected $ignoredFiles = [];

    public function __construct()
    {
        $this->basePath = app_path();
    }

    public function within(array $directories): self
    {
        $this->directories = $directories;

        return $this;
    }

    public function useBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function useRootNamespace(string $rootNamespace): self
    {
        $this->rootNamespace = $rootNamespace;

        return $this;
    }

    public function ignoringFiles(array $ignoredFiles): self
    {
        $this->ignoredFiles = $ignoredFiles;

        return $this;
    }

    public function discover(): array
    {
        if (empty($this->directories)) {
            return [];
        }

        $files = (new Finder())->files()->in($this->directories);

        return collect($files)
            ->reject(function (SplFileInfo $file) {
                return in_array($file->getPathname(), $this->ignoredFiles);
            })
            ->map(function (SplFileInfo $file) {
                return $this->fullQualifiedClassNameFromFile($file);
            })
            ->filter(function (string $settingsClass) {
                try {
                    return is_subclass_of($settingsClass, Settings::class);
                } catch (Throwable $e) {
                    return false;
                }
            })
            ->flatten()
            ->toArray();
    }

    protected function fullQualifiedClassNameFromFile(SplFileInfo $file): string
    {
        $class = trim(Str::replaceFirst($this->basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        $class = str_replace(
            [DIRECTORY_SEPARATOR, 'App\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        );

        return $this->rootNamespace . $class;
    }
}
