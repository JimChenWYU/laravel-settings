<?php

namespace Spatie\LaravelSettings\Migrations;

use Illuminate\Database\Migrations\Migration;

abstract class SettingsMigration extends Migration
{
    /** @var SettingsMigrator */
    protected $migrator;

    abstract public function up();

    public function __construct()
    {
        $this->migrator = app(SettingsMigrator::class);
    }
}
